<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameKitTemplate;
use App\Services\GameKitAiService;
use App\Services\GameKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GameKitAiController extends Controller
{
    public function __construct(private readonly GameKitAiService $ai, private readonly GameKitService $games) {}

    public function generate(Request $request)
    {
        $data = $request->validate([
            'age_group' => ['nullable', 'string', 'max:80'], 'theme' => ['nullable', 'string', 'max:120'],
            'activity_type' => ['nullable', 'string', 'max:80'], 'difficulty' => ['nullable', 'string', 'max:40'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:180'], 'language' => ['nullable', 'string', 'max:20'],
        ]);
        try {
            return response()->json(['cards' => $this->ai->generate($data)]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function defaultModel(Request $request)
    {
        $theme = $request->string('theme', 'Habilidades sociais')->toString();
        $cards = collect($this->games->generateCards($theme))->map(fn ($card) => [
            'context' => $card['prompt_a'], 'question' => $card['prompt_b'], 'options' => $card['options'],
        ])->values();

        return response()->json(['cards' => $cards]);
    }

    public function index(Request $request)
    {
        $templates = GameKitTemplate::with('cards')
            ->where('psychologist_id', $this->psychologistId($request))->latest()->limit(6)->get()
            ->map(function (GameKitTemplate $template) {
                $cards = $template->cards->where('version', $template->current_version)->sortBy('position')->values();

                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'format' => $template->format,
                    'age_group' => $template->age_group,
                    'theme' => $template->theme,
                    'activity_type' => $template->activity_type,
                    'difficulty' => $template->difficulty,
                    'duration_minutes' => $template->duration_minutes,
                    'status' => $template->status,
                    'current_version' => $template->current_version,
                    'cards' => $cards->map(fn ($card) => [
                        'id' => $card->id, 'position' => $card->position, 'context' => $card->context,
                        'question' => $card->question, 'options' => $card->options,
                    ])->values(),
                ];
            });

        return response()->json(['templates' => $templates]);
    }

    public function store(Request $request)
    {
        $data = $this->templateData($request);
        $template = DB::transaction(function () use ($data, $request) {
            $template = GameKitTemplate::create([
                ...$data['meta'], 'name' => $data['name'], 'psychologist_id' => $this->psychologistId($request),
                'format' => 'association_memory', 'status' => 'approved', 'current_version' => 1,
            ]);
            $this->saveCards($template, $data['cards'], 1);

            return $template;
        });

        return response()->json($this->withCurrentCards($template), 201);
    }

    public function show(Request $request, int $id)
    {
        return response()->json($this->withCurrentCards($this->owned($request, $id)));
    }

    public function update(Request $request, int $id)
    {
        $template = $this->owned($request, $id);
        if ($request->has('name') && ! $request->has('cards')) {
            $name = $request->validate(['name' => ['required', 'string', 'max:160']])['name'];
            $template->update(['name' => $name]);
            $template->refresh();

            return response()->json($template);
        }
        $data = $this->templateData($request);
        $version = $template->current_version + 1;
        DB::transaction(function () use ($template, $data, $version) {
            $template->update([...$data['meta'], 'name' => $data['name'], 'current_version' => $version]);
            $this->saveCards($template, $data['cards'], $version);
        });

        return response()->json($this->withCurrentCards($template->fresh()), 200);
    }

    public function rename(Request $request, int $id)
    {
        $template = $this->owned($request, $id);
        $name = $request->validate(['name' => ['required', 'string', 'max:160']])['name'];
        $template->update(['name' => $name]);
        $template->refresh();

        return response()->json($template);
    }

    public function duplicate(Request $request, int $id)
    {
        $source = $this->owned($request, $id)->load('cards');
        $copy = DB::transaction(function () use ($source, $request) {
            $copy = GameKitTemplate::create([
                'psychologist_id' => $this->psychologistId($request), 'name' => 'Cópia de '.$source->name,
                'format' => $source->format, 'age_group' => $source->age_group, 'theme' => $source->theme,
                'activity_type' => $source->activity_type, 'difficulty' => $source->difficulty,
                'duration_minutes' => $source->duration_minutes, 'status' => 'approved', 'current_version' => 1,
            ]);
            $this->saveCards($copy, $this->currentCards($source)->map(fn ($card) => $card->only(['context', 'question', 'options']))->all(), 1);

            return $copy;
        });

        return response()->json($this->withCurrentCards($copy), 201);
    }

    public function destroy(Request $request, int $id)
    {
        $this->owned($request, $id)->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function createSession(Request $request, int $id)
    {
        $template = $this->owned($request, $id)->load('cards');
        $session = $this->games->createSession([
            'age_group' => $template->age_group, 'theme' => $template->theme, 'difficulty' => $template->difficulty,
            'duration_minutes' => $template->duration_minutes,
        ], $this->psychologistId($request));
        $session->cards()->delete();
        foreach ($this->currentCards($template) as $position => $card) {
            $session->cards()->create(['position' => $position + 1, 'pair_key' => 'template_'.$template->id.'_'.($position + 1), 'prompt_a' => $card->context, 'prompt_b' => $card->question, 'options' => $card->options]);
        }

        return response()->json($session->load('cards'), 201);
    }

    public function generateCard(Request $request, int $id)
    {
        $template = $this->owned($request, $id);
        $data = $this->parameters($request);
        try {
            return response()->json(['card' => $this->ai->generate([
                ...$data, 'age_group' => ($data['age_group'] ?? null) ?: $template->age_group,
                'theme' => ($data['theme'] ?? null) ?: $template->theme,
                'activity_type' => ($data['activity_type'] ?? null) ?: $template->activity_type,
                'difficulty' => ($data['difficulty'] ?? null) ?: $template->difficulty,
                'duration_minutes' => ($data['duration_minutes'] ?? null) ?: $template->duration_minutes,
            ], true)[0]]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    private function parameters(Request $request): array
    {
        return $request->validate([
            'age_group' => ['required', 'string', 'max:80'], 'theme' => ['required', 'string', 'max:120'],
            'activity_type' => ['required', 'string', 'max:80'], 'difficulty' => ['required', 'string', 'max:40'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:180'], 'card_count' => ['nullable', 'integer', 'min:6', 'max:12'],
            'language' => ['nullable', 'string', 'max:20'],
        ]);
        $data['duration_minutes'] = $data['duration_minutes'] ?? 45;
    }

    private function templateData(Request $request): array
    {
        $meta = $this->parameters($request);
        $name = $request->validate(['name' => ['required', 'string', 'max:160']])['name'];
        unset($meta['card_count'], $meta['language']);
        $cards = $request->validate(['cards' => ['required', 'array', 'min:1', 'max:30'], 'cards.*.context' => ['required', 'string', 'max:1000'], 'cards.*.question' => ['required', 'string', 'max:500'], 'cards.*.options' => ['required', 'array', 'size:3'], 'cards.*.options.*' => ['required', 'string', 'max:300']])['cards'];

        return compact('meta', 'cards', 'name');
    }

    private function saveCards(GameKitTemplate $template, array $cards, int $version): void
    {
        foreach ($cards as $position => $card) {
            $template->cards()->create(['version' => $version, 'position' => $position + 1, 'context' => $card['context'], 'question' => $card['question'], 'options' => array_values($card['options'])]);
        }
    }

    private function currentCards(GameKitTemplate $template)
    {
        return $template->cards->where('version', $template->current_version)->sortBy('position')->values();
    }

    private function withCurrentCards(GameKitTemplate $template): GameKitTemplate
    {
        $template->load('cards');
        $current = $this->currentCards($template);
        $template->setRelation('cards', $current);
        $template->setRelation('currentCards', $current);

        return $template;
    }

    private function owned(Request $request, int $id): GameKitTemplate
    {
        return GameKitTemplate::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
    }

    private function psychologistId(Request $request): int
    {
        $id = $request->user()->loadMissing('psychologist')->psychologist?->id;
        abort_if(! $id, 403, 'Perfil de psicólogo não encontrado.');

        return (int) $id;
    }
}
