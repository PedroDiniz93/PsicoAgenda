<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameKitRoutine;
use App\Models\GameKitRoutineLink;
use App\Models\Patient;
use App\Services\GameKitAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameKitRoutineController extends Controller
{
    private const CATEGORIES = ['study', 'rest', 'leisure', 'self_care'];
    private const ICONS = ['BookOpen', 'Moon', 'Sparkles', 'Heart', 'CircleCheck', 'Coffee', 'Dumbbell', 'Users'];

    public function __construct(private readonly GameKitAiService $ai)
    {
    }

    public function index(Request $request)
    {
        $query = GameKitRoutine::with(['patient:id,name', 'currentVersion.blocks'])
            ->where('psychologist_id', $this->psychologistId($request));
        if ($request->filled('patient_id')) {
            $query->where('patient_id', (int) $request->input('patient_id'));
        }
        return response()->json(['routines' => $query->latest()->limit(50)->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->routineData($request);
        $routine = DB::transaction(function () use ($data, $request) {
            $routine = GameKitRoutine::create([
                'psychologist_id' => $this->psychologistId($request),
                'patient_id' => $data['patient_id'],
                'name' => $data['name'],
                'reference_date' => $data['reference_date'],
                'status' => 'active',
                'current_version' => 1,
            ]);
            $this->saveVersion($routine, $data['blocks'], 1, $data['summary']);
            return $routine;
        });
        return response()->json($this->withCurrent($routine), 201);
    }

    public function generateWithAi(Request $request)
    {
        $data = $request->validate([
            'theme' => ['required', 'string', 'max:160'],
            'age_group' => ['required', 'string', 'max:80'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'block_count' => ['required', 'integer', 'min:4', 'max:12'],
        ]);

        try {
            return response()->json(['blocks' => $this->ai->generateRoutine($data)]);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function show(Request $request, int $id)
    {
        return response()->json($this->withHistory($this->owned($request, $id)));
    }

    public function update(Request $request, int $id)
    {
        $routine = $this->owned($request, $id);
        $data = $this->routineData($request);
        $version = $routine->current_version + 1;
        DB::transaction(function () use ($routine, $data, $version) {
            $routine->update(['patient_id' => $data['patient_id'], 'name' => $data['name'], 'reference_date' => $data['reference_date'], 'current_version' => $version]);
            $this->saveVersion($routine, $data['blocks'], $version, $data['summary']);
        });
        return response()->json($this->withCurrent($routine->fresh()));
    }

    public function destroy(Request $request, int $id)
    {
        $this->owned($request, $id)->delete();
        return response()->json(['status' => 'deleted']);
    }

    public function link(Request $request, int $id)
    {
        $routine = $this->owned($request, $id);
        $token = Str::random(64);
        GameKitRoutineLink::where('gamekit_routine_id', $routine->id)->where('status', 'active')->update(['status' => 'revoked']);
        GameKitRoutineLink::create(['gamekit_routine_id' => $routine->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addHours(8), 'status' => 'active']);
        return response()->json(['url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/gamekit/routine/play/' . $token]);
    }

    public function publicView(string $token)
    {
        $link = GameKitRoutineLink::with(['routine.currentVersion.blocks'])
            ->where('token_hash', hash('sha256', $token))->where('status', 'active')->where('expires_at', '>', now())->first();
        abort_if(!$link, 404, 'Esta rotina não está mais disponível.');
        return response()->json(['routine' => $this->publicPayload($link->routine)]);
    }

    private function routineData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'patient_id' => ['nullable', 'integer'],
            'reference_date' => ['nullable', 'date'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'blocks' => ['required', 'array', 'min:1', 'max:40'],
            'blocks.*.start_time' => ['required', 'date_format:H:i'],
            'blocks.*.duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'blocks.*.title' => ['required', 'string', 'max:160'],
            'blocks.*.description' => ['nullable', 'string', 'max:1000'],
            'blocks.*.category' => ['required', 'string', 'in:' . implode(',', self::CATEGORIES)],
            'blocks.*.icon' => ['nullable', 'string', 'in:' . implode(',', self::ICONS)],
        ]);
        $patientId = $data['patient_id'] ?? null;
        if ($patientId !== null) {
            abort_if(!Patient::where('psychologist_id', $this->psychologistId($request))->whereKey($patientId)->exists(), 422, 'Paciente inválido.');
        }
        return [...$data, 'patient_id' => $patientId, 'blocks' => array_values($data['blocks'])];
    }

    private function saveVersion(GameKitRoutine $routine, array $blocks, int $version, ?string $summary): void
    {
        $record = $routine->versions()->create(['version' => $version, 'summary' => $summary]);
        foreach ($blocks as $position => $block) {
            $record->blocks()->create([...$block, 'position' => $position + 1, 'icon' => in_array($block['icon'] ?? null, self::ICONS, true) ? $block['icon'] : 'CircleCheck']);
        }
    }

    private function withCurrent(GameKitRoutine $routine): GameKitRoutine
    {
        return $routine->load(['patient:id,name', 'currentVersion.blocks']);
    }

    private function withHistory(GameKitRoutine $routine): GameKitRoutine
    {
        return $routine->load(['patient:id,name', 'versions.blocks']);
    }

    private function publicPayload(GameKitRoutine $routine): array
    {
        return ['name' => $routine->name, 'reference_date' => $routine->reference_date?->format('Y-m-d'), 'version' => $routine->current_version, 'blocks' => $routine->currentVersion?->blocks->map(fn ($block) => [...$block->only(['duration_minutes', 'title', 'description', 'category', 'icon']), 'start_time' => substr((string) $block->start_time, 0, 5)])->values() ?? []];
    }

    private function owned(Request $request, int $id): GameKitRoutine
    {
        return GameKitRoutine::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
    }

    private function psychologistId(Request $request): int
    {
        $id = $request->user()->loadMissing('psychologist')->psychologist?->id;
        abort_if(!$id, 403, 'Perfil de psicólogo não encontrado.');
        return (int) $id;
    }
}
