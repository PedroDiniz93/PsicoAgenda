<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Psychologist;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HomeDashboardController extends Controller
{
    private const CHARGEABLE_STATUSES = ['scheduled', 'done', 'missed'];

    private const TYPE_LABELS = [
        'online' => 'Online',
        'in_person' => 'Presencial',
    ];

    private const STATUS_LABELS = [
        'scheduled' => 'Agendado',
        'done' => 'Concluído',
        'missed' => 'Falta',
        'canceled' => 'Cancelado',
    ];

    public function show(Request $request)
    {
        $psychologist = $this->psychologist($request);
        $timezone = $psychologist->timezone ?? config('app.timezone');
        $nowLocal = Carbon::now($timezone);
        $now = $nowLocal->copy()->timezone(config('app.timezone'));

        $summaryQuery = Appointment::query()
            ->where('psychologist_id', $psychologist->id)
            ->whereIn('status', self::CHARGEABLE_STATUSES);

        $paidBuilder = (clone $summaryQuery)->whereNotNull('paid_at');
        $pendingBuilder = (clone $summaryQuery)
            ->whereNull('paid_at')
            ->whereIn('status', self::CHARGEABLE_STATUSES);

        $paidCount = (clone $paidBuilder)->count();
        $paidValue = (clone $paidBuilder)->sum('price');
        $pendingCount = (clone $pendingBuilder)->count();
        $pendingValue = (clone $pendingBuilder)->sum('price');
        $totalSessions = (clone $summaryQuery)->count();
        $uniquePatients = (clone $summaryQuery)->distinct('patient_id')->count('patient_id');
        $doneCount = (clone $summaryQuery)->where('status', 'done')->count();
        $missedCount = (clone $summaryQuery)->where('status', 'missed')->count();
        $attendanceBase = $doneCount + $missedCount;
        $attendanceRate = $attendanceBase > 0 ? $doneCount / $attendanceBase : null;
        $avgTicket = $paidCount > 0 ? $paidValue / $paidCount : 0;

        $nextAppointments = Appointment::query()
            ->with('patient:id,name')
            ->where('psychologist_id', $psychologist->id)
            ->where('status', 'scheduled')
            ->where('start_at', '>=', $now)
            ->orderBy('start_at')
            ->limit(3)
            ->get()
            ->map(fn (Appointment $appointment) => $this->serializeNextAppointment($appointment, $timezone))
            ->values();

        [$weekStartLocal, $weekEndLocal] = $this->resolveCurrentWeekRange($nowLocal);
        $weeklyAppointments = Appointment::query()
            ->where('psychologist_id', $psychologist->id)
            ->where('status', 'done')
            ->whereBetween('start_at', [
                $weekStartLocal->copy()->timezone(config('app.timezone')),
                $weekEndLocal->copy()->timezone(config('app.timezone')),
            ])
            ->get(['start_at']);

        $weeklyAttendances = $this->buildWeeklyAttendances($weeklyAppointments, $timezone, $weekStartLocal);

        return response()->json([
            'hero' => [
                'title' => sprintf('Olá, %s', $psychologist->name ?: $request->user()->name ?: 'Psicólogo(a)'),
                'description' => $totalSessions > 0
                    ? sprintf(
                        'Você tem %s no período acompanhado.',
                        $this->pluralize($totalSessions, 'sessão registrada', 'sessões registradas')
                    )
                    : 'Acompanhe a rotina clínica e financeira assim que houver sessões no período.',
            ],
            'metrics' => [
                [
                    'id' => 'sessions',
                    'label' => 'Sessões no período',
                    'value' => $totalSessions,
                    'detail' => $this->pluralize($uniquePatients, 'paciente único', 'pacientes únicos'),
                    'icon' => 'CalendarCheck2',
                    'accent' => 'primary',
                ],
                [
                    'id' => 'attendance',
                    'label' => 'Comparecimento',
                    'value' => $attendanceRate === null ? 'Sem dados' : sprintf('%.1f%%', round($attendanceRate * 1000) / 10),
                    'detail' => sprintf('%d concluídas, %d faltas', $doneCount, $missedCount),
                    'icon' => 'UserCheck',
                    'accent' => 'success',
                ],
                [
                    'id' => 'ticket',
                    'label' => 'Ticket médio',
                    'value' => $this->formatMoney($avgTicket),
                    'detail' => 'Média dos atendimentos pagos',
                    'icon' => 'BadgeDollarSign',
                    'accent' => 'tertiary',
                ],
                [
                    'id' => 'pending',
                    'label' => 'A receber',
                    'value' => $this->formatMoney($pendingValue),
                    'detail' => $this->pluralize($pendingCount, 'sessão pendente', 'sessões pendentes'),
                    'icon' => 'Clock3',
                    'accent' => 'warning',
                ],
            ],
            'next_patients' => $nextAppointments,
            'weekly_attendances' => $weeklyAttendances,
        ]);
    }

    private function psychologist(Request $request): Psychologist
    {
        $user = $request->user()->loadMissing('psychologist');
        $psychologist = $user->psychologist;

        abort_if(!$psychologist, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        return $psychologist;
    }

    private function serializeNextAppointment(Appointment $appointment, string $timezone): array
    {
        $startLocal = $appointment->start_at?->copy()->setTimezone($timezone);
        $endLocal = $appointment->end_at?->copy()->setTimezone($timezone);

        return [
            'id' => $appointment->id,
            'patient' => [
                'id' => $appointment->patient_id,
                'name' => $appointment->patient?->name ?? 'Paciente sem nome',
            ],
            'start_at' => $appointment->start_at?->toIso8601String(),
            'end_at' => $appointment->end_at?->toIso8601String(),
            'date_label' => $startLocal?->format('d/m/Y') ?? '—',
            'time_label' => $startLocal && $endLocal
                ? sprintf('%s - %s', $startLocal->format('H:i'), $endLocal->format('H:i'))
                : '—',
            'modality' => $appointment->type ?? 'online',
            'modality_label' => self::TYPE_LABELS[$appointment->type ?? 'online'] ?? 'Online',
            'status' => $appointment->status,
            'status_label' => self::STATUS_LABELS[$appointment->status] ?? Str::headline((string) $appointment->status),
            'status_accent' => 'primary',
        ];
    }

    private function resolveCurrentWeekRange(Carbon $nowLocal): array
    {
        $start = $nowLocal->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $start->copy()->addDays(4)->endOfDay();

        return [$start, $end];
    }

    private function buildWeeklyAttendances(iterable $appointments, string $timezone, Carbon $weekStartLocal): array
    {
        $counts = array_fill(0, 5, 0);

        foreach ($appointments as $appointment) {
            $localStart = $appointment->start_at?->copy()->setTimezone($timezone);
            if (!$localStart) {
                continue;
            }

            $dayIndex = (int) $localStart->dayOfWeekIso - 1;
            if ($dayIndex >= 0 && $dayIndex < 5) {
                $counts[$dayIndex]++;
            }
        }

        $days = [
            ['key' => 'mon', 'label' => 'Seg', 'full_label' => 'Segunda'],
            ['key' => 'tue', 'label' => 'Ter', 'full_label' => 'Terça'],
            ['key' => 'wed', 'label' => 'Qua', 'full_label' => 'Quarta'],
            ['key' => 'thu', 'label' => 'Qui', 'full_label' => 'Quinta'],
            ['key' => 'fri', 'label' => 'Sex', 'full_label' => 'Sexta'],
        ];

        $maxCount = max($counts) ?: 0;
        $total = array_sum($counts);

        return [
            'label' => 'Semana atual',
            'from' => $weekStartLocal->toDateString(),
            'to' => $weekStartLocal->copy()->addDays(4)->toDateString(),
            'total' => $total,
            'average_daily' => round($total / 5, 1),
            'days' => array_map(
                fn (array $day, int $index) => [
                    'key' => $day['key'],
                    'label' => $day['label'],
                    'full_label' => $day['full_label'],
                    'date_label' => $weekStartLocal->copy()->addDays($index)->format('d/m'),
                    'count' => $counts[$index],
                    'ratio' => $maxCount > 0 ? round(($counts[$index] / $maxCount) * 100) : 0,
                ],
                $days,
                array_keys($days)
            ),
        ];
    }

    private function pluralize(int $count, string $singular, string $plural): string
    {
        return sprintf('%d %s', $count, $count === 1 ? $singular : $plural);
    }

    private function formatMoney(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
