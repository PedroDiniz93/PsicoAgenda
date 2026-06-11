<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Psychologist;
use App\Models\PsychologistAvailabilityRule;
use App\Models\PsychologistScheduleBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AvailabilityController extends Controller
{
    private function psychologist(Request $request): Psychologist
    {
        $user = $request->user()->loadMissing('psychologist');
        $psychologist = $user->psychologist;

        abort_if(!$psychologist, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        return $psychologist;
    }

    public function index(Request $request)
    {
        $psychologist = $this->psychologist($request);
        [$from, $to] = $this->range($request, $psychologist);

        $rules = PsychologistAvailabilityRule::query()
            ->where('psychologist_id', $psychologist->id)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        $blocks = PsychologistScheduleBlock::query()
            ->where('psychologist_id', $psychologist->id)
            ->where('starts_at', '<=', $to)
            ->where('ends_at', '>=', $from)
            ->orderBy('starts_at')
            ->get();

        return response()->json([
            'daily_appointment_limit' => $psychologist->daily_appointment_limit,
            'rules' => $rules->map(fn (PsychologistAvailabilityRule $rule) => $this->serializeRule($rule))->values(),
            'blocks' => $blocks->map(fn (PsychologistScheduleBlock $block) => $this->serializeBlock($block))->values(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $psychologist = $this->psychologist($request);
        $data = $request->validate([
            'daily_appointment_limit' => ['nullable', 'integer', 'min:0', 'max:40'],
            'rules' => ['array'],
            'rules.*.weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'rules.*.start_time' => ['required', 'date_format:H:i'],
            'rules.*.end_time' => ['required', 'date_format:H:i'],
            'rules.*.is_active' => ['sometimes', 'boolean'],
        ], [
            'rules.*.end_time.after' => 'O horário final deve ser depois do horário inicial.',
            'daily_appointment_limit.max' => 'O limite diário deve ser no máximo 40 atendimentos.',
        ]);

        foreach ($data['rules'] ?? [] as $rule) {
            abort_if(
                ($rule['is_active'] ?? true) !== false && $rule['start_time'] >= $rule['end_time'],
                422,
                'O horário final da disponibilidade deve ser depois do horário inicial.'
            );
        }

        DB::transaction(function () use ($psychologist, $data) {
            $psychologist->daily_appointment_limit = empty($data['daily_appointment_limit'])
                ? null
                : (int) $data['daily_appointment_limit'];
            $psychologist->save();

            PsychologistAvailabilityRule::query()
                ->where('psychologist_id', $psychologist->id)
                ->delete();

            foreach ($data['rules'] ?? [] as $rule) {
                if (($rule['is_active'] ?? true) === false) {
                    continue;
                }

                PsychologistAvailabilityRule::create([
                    'psychologist_id' => $psychologist->id,
                    'weekday' => (int) $rule['weekday'],
                    'start_time' => $rule['start_time'],
                    'end_time' => $rule['end_time'],
                    'is_active' => true,
                ]);
            }
        });

        return $this->index($request);
    }

    public function storeBlock(Request $request)
    {
        $psychologist = $this->psychologist($request);
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'type' => ['required', Rule::in(['block', 'vacation'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'starts_at.required' => 'Informe o início do bloqueio.',
            'ends_at.required' => 'Informe o fim do bloqueio.',
            'ends_at.after' => 'O fim do bloqueio deve ser depois do início.',
        ]);

        $block = PsychologistScheduleBlock::create([
            'psychologist_id' => $psychologist->id,
            'starts_at' => Carbon::parse($data['starts_at']),
            'ends_at' => Carbon::parse($data['ends_at']),
            'type' => $data['type'],
            'reason' => $this->nullableTrim($data['reason'] ?? null),
        ]);

        return response()->json([
            'block' => $this->serializeBlock($block->refresh()),
        ], 201);
    }

    public function destroyBlock(Request $request, int $block)
    {
        $psychologist = $this->psychologist($request);
        $block = PsychologistScheduleBlock::query()
            ->where('psychologist_id', $psychologist->id)
            ->findOrFail($block);

        $block->delete();

        return response()->json(['success' => true]);
    }

    private function range(Request $request, Psychologist $psychologist): array
    {
        $timezone = $psychologist->timezone ?? config('app.timezone');

        try {
            $from = $request->filled('from')
                ? Carbon::parse($request->string('from')->toString(), $timezone)->startOfDay()
                : Carbon::now($timezone)->startOfWeek();
            $to = $request->filled('to')
                ? Carbon::parse($request->string('to')->toString(), $timezone)->endOfDay()
                : $from->copy()->addDays(6)->endOfDay();
        } catch (\Throwable) {
            abort(422, 'Intervalo de disponibilidade inválido.');
        }

        return [
            $from->copy()->timezone(config('app.timezone')),
            $to->copy()->timezone(config('app.timezone')),
        ];
    }

    private function serializeRule(PsychologistAvailabilityRule $rule): array
    {
        return [
            'id' => $rule->id,
            'weekday' => $rule->weekday,
            'start_time' => substr((string) $rule->start_time, 0, 5),
            'end_time' => substr((string) $rule->end_time, 0, 5),
            'is_active' => $rule->is_active,
        ];
    }

    private function serializeBlock(PsychologistScheduleBlock $block): array
    {
        return [
            'id' => $block->id,
            'starts_at' => $block->starts_at?->toIso8601String(),
            'ends_at' => $block->ends_at?->toIso8601String(),
            'type' => $block->type,
            'reason' => $block->reason,
        ];
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
