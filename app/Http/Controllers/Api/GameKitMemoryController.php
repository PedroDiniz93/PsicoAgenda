<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameKitMemoryGame;
use App\Models\GameKitMemorySession;
use App\Services\GameKitAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameKitMemoryController extends Controller
{
    private const ICONS = ['Wind', 'HeartHandshake', 'PauseCircle', 'Shield', 'Sun', 'Sprout', 'MessageCircleHeart', 'HelpCircle', 'Compass', 'Sparkles', 'Cloud', 'CircleCheck'];

    public function __construct(private readonly GameKitAiService $ai) {}

    public function generateWithAi(Request $request)
    {
        $data = $request->validate([
            'age_group' => ['required', 'string', 'max:80'],
            'theme' => ['required', 'string', 'max:120'],
            'difficulty' => ['required', 'string', 'max:40'],
            'pair_count' => ['required', 'integer', 'in:6,8,12'],
        ]);

        try {
            return response()->json(['pairs' => $this->ai->generateMemory($data)]);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function index(Request $request)
    {
        return response()->json([
            'games' => GameKitMemoryGame::withCount('pairs')
                ->with('pairs')
                ->where('psychologist_id', $this->psychologistId($request))
                ->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->gameData($request);
        $game = DB::transaction(function () use ($data, $request) {
            $game = GameKitMemoryGame::create([
                ...$data['meta'],
                'psychologist_id' => $this->psychologistId($request),
                'pair_count' => count($data['pairs']),
                'status' => 'saved',
            ]);
            $this->savePairs($game, $data['pairs']);
            return $game;
        });

        return response()->json($game->load('pairs'), 201);
    }

    public function show(Request $request, int $id)
    {
        return response()->json($this->ownedGame($request, $id)->load('pairs'));
    }

    public function update(Request $request, int $id)
    {
        $game = $this->ownedGame($request, $id);
        $data = $this->gameData($request);
        DB::transaction(function () use ($game, $data) {
            $game->update([...$data['meta'], 'pair_count' => count($data['pairs'])]);
            $game->pairs()->delete();
            $this->savePairs($game, $data['pairs']);
        });

        return response()->json($game->fresh()->load('pairs'));
    }

    public function destroy(Request $request, int $id)
    {
        $this->ownedGame($request, $id)->delete();
        return response()->json(['status' => 'deleted']);
    }

    public function duplicate(Request $request, int $id)
    {
        $source = $this->ownedGame($request, $id)->load('pairs');
        $copy = DB::transaction(function () use ($source, $request) {
            $copy = GameKitMemoryGame::create([
                'psychologist_id' => $this->psychologistId($request),
                'name' => 'Cópia de ' . $source->name,
                'age_group' => $source->age_group,
                'theme' => $source->theme,
                'difficulty' => $source->difficulty,
                'pair_count' => $source->pair_count,
                'status' => 'saved',
            ]);
            $this->savePairs($copy, $source->pairs->map(fn ($pair) => $pair->only(['label_a', 'label_b', 'concept', 'feedback', 'icon', 'accent']))->all());
            return $copy;
        });

        return response()->json($copy->load('pairs'), 201);
    }

    public function createSession(Request $request, int $id)
    {
        $game = $this->ownedGame($request, $id);
        $session = GameKitMemorySession::create([
            'gamekit_memory_game_id' => $game->id,
            'psychologist_id' => $this->psychologistId($request),
            'status' => 'draft',
        ]);

        return response()->json($session->load(['game.pairs', 'result']), 201);
    }

    public function session(Request $request, int $id)
    {
        $session = GameKitMemorySession::with(['game.pairs', 'result'])
            ->where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
        return response()->json($session);
    }

    public function link(Request $request, int $id)
    {
        $session = GameKitMemorySession::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
        abort_if($session->status === 'finished', 422, 'Esta atividade já foi concluída.');
        $token = Str::random(64);
        $session->forceFill([
            'public_token_hash' => hash('sha256', $token),
            'public_token_expires_at' => now()->addHours(8),
            'status' => 'active',
            'started_at' => $session->started_at ?? now(),
        ])->save();

        return response()->json(['url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/gamekit/memory/play/' . $token]);
    }

    public function play(string $token)
    {
        $session = $this->findPublicSession($token);
        return response()->json([
            'session' => [
                'id' => $session->id,
                'theme' => $session->game->theme,
                'name' => $session->game->name,
                'pairs' => $session->game->pairs->map(fn ($pair) => [
                    'id' => $pair->id, 'label_a' => $pair->label_a, 'label_b' => $pair->label_b,
                    'concept' => $pair->concept, 'feedback' => $pair->feedback, 'icon' => $pair->icon, 'accent' => $pair->accent,
                ])->values(),
            ],
        ]);
    }

    public function result(Request $request, string $token)
    {
        $session = $this->findPublicSession($token);
        $data = $request->validate([
            'attempts' => ['required', 'integer', 'min:0', 'max:1000'],
            'matched_pairs' => ['required', 'integer', 'min:0', 'max:100'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'completed' => ['required', 'boolean'],
        ]);
        $completed = $data['completed'] && $data['matched_pairs'] >= $session->game->pair_count;
        $result = $session->result()->updateOrCreate([], [
            'attempts' => $data['attempts'], 'matched_pairs' => min($data['matched_pairs'], $session->game->pair_count),
            'duration_seconds' => $data['duration_seconds'] ?? null, 'completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ]);
        if ($completed) {
            $session->forceFill(['status' => 'finished', 'finished_at' => now(), 'public_token_hash' => null])->save();
        }
        return response()->json(['status' => $completed ? 'finished' : 'saved', 'result' => $result]);
    }

    private function gameData(Request $request): array
    {
        $meta = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'age_group' => ['required', 'string', 'max:80'],
            'theme' => ['required', 'string', 'max:120'],
            'difficulty' => ['required', 'string', 'max:40'],
        ]);
        $pairs = $request->validate([
            'pairs' => ['required', 'array', 'min:3', 'max:12'],
            'pairs.*.label_a' => ['required', 'string', 'max:160'],
            'pairs.*.label_b' => ['required', 'string', 'max:160'],
            'pairs.*.concept' => ['required', 'string', 'max:160'],
            'pairs.*.feedback' => ['required', 'string', 'max:500'],
            'pairs.*.icon' => ['nullable', 'string', 'in:' . implode(',', self::ICONS)],
            'pairs.*.accent' => ['nullable', 'string', 'max:30'],
        ])['pairs'];
        return ['meta' => $meta, 'pairs' => $pairs];
    }

    private function savePairs(GameKitMemoryGame $game, array $pairs): void
    {
        foreach (array_values($pairs) as $position => $pair) {
            $game->pairs()->create([
                'position' => $position + 1,
                'label_a' => $pair['label_a'], 'label_b' => $pair['label_b'],
                'concept' => $pair['concept'], 'feedback' => $pair['feedback'],
                'icon' => in_array($pair['icon'] ?? null, self::ICONS, true) ? $pair['icon'] : 'Sparkles',
                'accent' => $pair['accent'] ?? 'sage',
            ]);
        }
    }

    private function ownedGame(Request $request, int $id): GameKitMemoryGame
    {
        return GameKitMemoryGame::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
    }

    private function findPublicSession(string $token): GameKitMemorySession
    {
        $session = GameKitMemorySession::with(['game.pairs'])
            ->where('public_token_hash', hash('sha256', $token))
            ->where('status', 'active')->where('public_token_expires_at', '>', now())->first();
        abort_if(!$session, 404, 'Esta atividade não está mais disponível.');
        return $session;
    }

    private function psychologistId(Request $request): int
    {
        $id = $request->user()->loadMissing('psychologist')->psychologist?->id;
        abort_if(!$id, 403, 'Perfil de psicólogo não encontrado.');
        return (int) $id;
    }
}
