<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameKitResponse;
use App\Models\GameKitSession;
use App\Models\Patient;
use App\Services\GameKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameKitController extends Controller
{
    public function __construct(private readonly GameKitService $service) {}

    public function templates(): array
    {
        return ['templates' => $this->service->templates()];
    }

    public function index(Request $request)
    {
        $psychologistId = $this->psychologistId($request);

        $query = GameKitSession::with('cards')
            ->where('psychologist_id', $psychologistId);

        if ($request->boolean('with_responses')) {
            $query->whereHas('responses')->withCount('responses');
        }

        return response()->json($query->latest()->limit(20)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'age_group' => ['required', 'string', 'max:80'],
            'theme' => ['required', 'string', 'max:120'],
            'difficulty' => ['required', 'string', 'max:40'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:180'],
        ]);
        $data['duration_minutes'] = $data['duration_minutes'] ?? 45;

        return response()->json(
            $this->service->createSession($data, $this->psychologistId($request)),
            201
        );
    }

    public function show(Request $request, int $id)
    {
        return response()->json($this->ownedSession($request, $id)->load(['cards', 'patient', 'responses.card']));
    }

    public function update(Request $request, int $id)
    {
        $session = $this->ownedSession($request, $id);
        abort_if($session->status !== 'draft', 422, 'A sessão já foi iniciada.');

        $data = $request->validate([
            'cards' => ['required', 'array', 'min:1', 'max:30'],
            'cards.*.prompt_a' => ['required', 'string', 'max:1000'],
            'cards.*.prompt_b' => ['required', 'string', 'max:500'],
            'cards.*.options' => ['required', 'array', 'size:3'],
            'cards.*.options.*' => ['required', 'string', 'max:300'],
        ]);

        $session->cards()->delete();
        foreach ($data['cards'] as $index => $card) {
            $session->cards()->create([
                'position' => $index + 1,
                'pair_key' => 'custom_'.($index + 1),
                ...$card,
            ]);
        }

        return response()->json($session->load('cards'));
    }

    public function link(Request $request, int $id)
    {
        $session = $this->ownedSession($request, $id);
        abort_if($session->status === 'finished', 422, 'A sessão já foi encerrada.');

        $token = $this->service->issuePublicToken($session);

        return response()->json(['url' => rtrim(config('app.frontend_url', config('app.url')), '/').'/gamekit/play/'.$token]);
    }

    public function finish(Request $request, int $id)
    {
        $session = $this->ownedSession($request, $id);
        $this->service->finish($session);

        return response()->json(['status' => 'finished']);
    }

    public function review(Request $request, int $id)
    {
        $session = $this->ownedSession($request, $id);
        $data = $request->validate([
            'patient_id' => ['nullable', 'integer'],
            'responses' => ['required', 'array'],
            'responses.*.id' => ['required', 'integer'],
            'responses.*.include_in_record' => ['required', 'boolean'],
            'responses.*.note' => ['nullable', 'string', 'max:2000'],
        ]);

        $patientId = $data['patient_id'] ?? null;
        if ($patientId !== null) {
            abort_if(! Patient::where('psychologist_id', $this->psychologistId($request))->whereKey($patientId)->exists(), 422, 'Paciente inválido.');
        }
        $session->update(['patient_id' => $patientId]);

        foreach ($data['responses'] as $item) {
            $response = $session->responses()->find($item['id']);
            if ($response) {
                $response->update([
                    'reviewed' => true,
                    'include_in_record' => $item['include_in_record'],
                    'note' => $item['note'] ?? null,
                ]);
            }
        }

        return response()->json(['status' => 'reviewed']);
    }

    public function play(string $token)
    {
        $session = $this->service->findByToken($token);
        abort_if(! $session, 404, 'Esta sessão não está disponível.');

        return response()->json([
            'session' => [
                'id' => $session->id,
                'format' => $session->format,
                'theme' => $session->theme,
                'cards' => $session->cards->map(fn ($card) => [
                    'id' => $card->id,
                    'position' => $card->position,
                    'context' => $card->prompt_a,
                    'question' => $card->prompt_b,
                    'prompt_a' => $card->prompt_a,
                    'prompt_b' => $card->prompt_b,
                    'options' => $card->options,
                ]),
            ],
        ]);
    }

    public function respond(Request $request, string $token)
    {
        $session = $this->service->findByToken($token);
        abort_if(! $session, 404, 'Esta sessão não está disponível.');

        $data = $request->validate([
            'card_id' => ['required', 'integer'],
            'answer' => ['required', 'string', 'max:500'],
            'participant_key' => ['nullable', 'string', 'max:64'],
        ]);
        $card = $session->cards->firstWhere('id', (int) $data['card_id']);
        abort_if(! $card || ! in_array($data['answer'], $card->options ?? [], true), 422, 'Resposta inválida.');

        $participantKey = ($data['participant_key'] ?? null) ?: hash('sha256', Str::uuid()->toString());
        $response = GameKitResponse::updateOrCreate(
            ['gamekit_card_id' => $card->id, 'participant_key' => $participantKey],
            ['gamekit_session_id' => $session->id, 'answer' => $data['answer']]
        );

        $answeredCards = $session->responses()
            ->where('participant_key', $participantKey)
            ->distinct('gamekit_card_id')
            ->count('gamekit_card_id');
        if ($answeredCards >= $session->cards->count()) {
            $this->service->finish($session);
        }

        return response()->json(['participant_key' => $participantKey, 'response_id' => $response->id]);
    }

    public function finishPublic(string $token)
    {
        $session = $this->service->findByToken($token);
        abort_if(! $session, 404, 'Esta sessão não está disponível.');
        $this->service->finish($session);

        return response()->json(['status' => 'finished']);
    }

    private function ownedSession(Request $request, int $id): GameKitSession
    {
        return GameKitSession::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
    }

    private function psychologistId(Request $request): int
    {
        $id = $request->user()->loadMissing('psychologist')->psychologist?->id;
        abort_if(! $id, 403, 'Perfil de psicólogo não encontrado.');

        return (int) $id;
    }
}
