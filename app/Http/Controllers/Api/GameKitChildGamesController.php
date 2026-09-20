<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameKitHangmanGame;
use App\Models\GameKitHangmanSession;
use App\Models\GameKitTicTacToeSession;
use App\Services\GameKitAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameKitChildGamesController extends Controller
{
    public function __construct(private readonly GameKitAiService $ai) {}

    public function generateHangman(Request $request)
    {
        $data = $request->validate(['age_group' => ['required', 'string', 'max:80'], 'theme' => ['required', 'string', 'max:120'], 'word_count' => ['required', 'integer', 'in:4,6,8,10,12']]);
        try {
            return response()->json(['words' => $this->ai->generateHangmanWords($data)]);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function hangmanGames(Request $request)
    {
        return response()->json(['games' => GameKitHangmanGame::where('psychologist_id', $this->psychologistId($request))->latest()->limit(30)->get()]);
    }

    public function storeHangman(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'age_group' => ['required', 'string', 'max:80'], 'theme' => ['required', 'string', 'max:120'], 'words' => ['required', 'array', 'min:4', 'max:20'], 'words.*' => ['required', 'string', 'min:3', 'max:24']]);
        $data['words'] = $this->cleanWords($data['words']);
        abort_if(count($data['words']) < 4, 422, 'Adicione pelo menos quatro palavras diferentes.');
        $game = GameKitHangmanGame::create([...$data, 'word_count' => count($data['words']), 'psychologist_id' => $this->psychologistId($request), 'status' => 'saved']);

        return response()->json($game, 201);
    }

    public function updateHangman(Request $request, int $id)
    {
        $game = $this->ownedHangman($request, $id);
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'age_group' => ['required', 'string', 'max:80'], 'theme' => ['required', 'string', 'max:120'], 'words' => ['required', 'array', 'min:4', 'max:20'], 'words.*' => ['required', 'string', 'min:3', 'max:24']]);
        $data['words'] = $this->cleanWords($data['words']);
        abort_if(count($data['words']) < 4, 422, 'Adicione pelo menos quatro palavras diferentes.');
        $game->update([...$data, 'word_count' => count($data['words'])]);

        return response()->json($game->fresh());
    }

    public function destroyHangman(Request $request, int $id)
    {
        $this->ownedHangman($request, $id)->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function createHangmanSession(Request $request, int $id)
    {
        $game = $this->ownedHangman($request, $id);
        $session = GameKitHangmanSession::create(['gamekit_hangman_game_id' => $game->id, 'psychologist_id' => $this->psychologistId($request), 'status' => 'draft']);

        return response()->json($session->load('game'), 201);
    }

    public function linkHangman(Request $request, int $id)
    {
        $session = GameKitHangmanSession::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
        $token = Str::random(64);
        $session->forceFill(['public_token_hash' => hash('sha256', $token), 'public_token_expires_at' => now()->addHours(8), 'status' => 'active', 'started_at' => $session->started_at ?? now()])->save();

        return response()->json(['url' => rtrim(config('app.frontend_url', config('app.url')), '/').'/gamekit/hangman/play/'.$token]);
    }

    public function hangmanPlay(string $token)
    {
        $session = $this->publicHangman($token);

        return response()->json(['session' => ['id' => $session->id, 'name' => $session->game->name, 'theme' => $session->game->theme, 'words' => $session->game->words]]);
    }

    public function hangmanResult(Request $request, string $token)
    {
        $session = $this->publicHangman($token);
        $data = $request->validate(['completed_words' => ['required', 'integer', 'min:0', 'max:100'], 'misses' => ['required', 'integer', 'min:0', 'max:1000'], 'completed' => ['required', 'boolean']]);
        $completed = $data['completed'] && $data['completed_words'] >= $session->game->word_count;
        $session->forceFill(['result' => [...$data, 'completed' => $completed], 'status' => $completed ? 'finished' : 'active', 'finished_at' => $completed ? now() : null, 'public_token_hash' => $completed ? null : $session->public_token_hash])->save();

        return response()->json(['status' => $completed ? 'finished' : 'saved']);
    }

    public function createTicTacToe(Request $request)
    {
        $data = $request->validate(['mode' => ['nullable', 'in:computer,local']]);

        return response()->json(GameKitTicTacToeSession::create(['psychologist_id' => $this->psychologistId($request), 'mode' => $data['mode'] ?? 'computer', 'status' => 'draft']), 201);
    }

    public function linkTicTacToe(Request $request, int $id)
    {
        $session = GameKitTicTacToeSession::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
        $token = Str::random(64);
        $session->forceFill(['public_token_hash' => hash('sha256', $token), 'public_token_expires_at' => now()->addHours(8), 'status' => 'active', 'started_at' => $session->started_at ?? now()])->save();

        return response()->json(['url' => rtrim(config('app.frontend_url', config('app.url')), '/').'/gamekit/tictactoe/play/'.$token]);
    }

    public function ticTacToePlay(string $token)
    {
        $session = GameKitTicTacToeSession::where('public_token_hash', hash('sha256', $token))->where('status', 'active')->where('public_token_expires_at', '>', now())->first();
        abort_if(! $session, 404, 'Esta partida não está mais disponível.');

        return response()->json(['session' => ['id' => $session->id, 'mode' => $session->mode]]);
    }

    public function ticTacToeResult(Request $request, string $token)
    {
        $session = GameKitTicTacToeSession::where('public_token_hash', hash('sha256', $token))->where('status', 'active')->where('public_token_expires_at', '>', now())->first();
        abort_if(! $session, 404, 'Esta partida não está mais disponível.');
        $data = $request->validate(['result' => ['required', 'in:win,loss,draw'], 'moves' => ['required', 'array', 'max:100']]);
        $session->forceFill(['result' => $data['result'], 'moves' => $data['moves'], 'status' => 'finished', 'finished_at' => now(), 'public_token_hash' => null])->save();

        return response()->json(['status' => 'finished']);
    }

    private function cleanWords(array $words): array
    {
        return collect($words)->map(fn ($word) => mb_strtolower(trim(preg_replace('/[^\p{L} ]/u', '', (string) $word))))->filter(fn ($word) => mb_strlen($word) >= 3)->unique()->values()->all();
    }

    private function ownedHangman(Request $request, int $id): GameKitHangmanGame
    {
        return GameKitHangmanGame::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
    }

    private function publicHangman(string $token): GameKitHangmanSession
    {
        $session = GameKitHangmanSession::with('game')->where('public_token_hash', hash('sha256', $token))->where('status', 'active')->where('public_token_expires_at', '>', now())->first();
        abort_if(! $session, 404, 'Esta atividade não está mais disponível.');

        return $session;
    }

    private function psychologistId(Request $request): int
    {
        $id = $request->user()->loadMissing('psychologist')->psychologist?->id;
        abort_if(! $id, 403, 'Perfil de psicólogo não encontrado.');

        return (int) $id;
    }
}
