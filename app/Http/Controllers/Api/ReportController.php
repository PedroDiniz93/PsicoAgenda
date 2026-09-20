<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentReportRequest;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function appointments(AppointmentReportRequest $request)
    {
        $psychologistId = $this->psychologistId($request);
        [$from, $to] = $this->resolveRange($request);

        $baseQuery = Appointment::where('psychologist_id', $psychologistId);

        if ($from) {
            $baseQuery->where('start_at', '>=', $from);
        }

        if ($to) {
            $baseQuery->where('start_at', '<=', $to);
        }

        $paidBuilder = (clone $baseQuery)->whereNotNull('paid_at');
        $pendingBuilder = (clone $baseQuery)
            ->whereNull('paid_at')
            ->whereIn('status', ['scheduled', 'done', 'missed']);

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw('COUNT(DISTINCT patient_id) as unique_patients')
            ->selectRaw('SUM(CASE WHEN paid_at IS NOT NULL THEN 1 ELSE 0 END) as paid_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN paid_at IS NOT NULL THEN price ELSE 0 END), 0) as paid_value')
            ->selectRaw("SUM(CASE WHEN paid_at IS NULL AND status IN ('scheduled', 'done', 'missed') THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN paid_at IS NULL AND status IN ('scheduled', 'done', 'missed') THEN price ELSE 0 END), 0) as pending_value")
            ->selectRaw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done_count")
            ->selectRaw("SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_count")
            ->selectRaw("SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed_count")
            ->toBase()
            ->first();

        $paidValue = (float) $summary->paid_value;
        $paidCount = (int) $summary->paid_count;
        $pendingValue = (float) $summary->pending_value;
        $pendingCount = (int) $summary->pending_count;
        $totalSessions = (int) $summary->total_sessions;
        $uniquePatients = (int) $summary->unique_patients;

        $paidList = $this->formatAppointmentsForReport(clone $paidBuilder);
        $pendingList = $this->formatAppointmentsForReport(clone $pendingBuilder);

        $doneList = $this->formatAppointmentsForReport(
            (clone $baseQuery)->where('status', 'done')
        );
        $canceledList = $this->formatAppointmentsForReport(
            (clone $baseQuery)->where('status', 'canceled')
        );
        $missedList = $this->formatAppointmentsForReport(
            (clone $baseQuery)->where('status', 'missed')
        );

        $doneCount = (int) $summary->done_count;
        $canceledCount = (int) $summary->canceled_count;
        $missedCount = (int) $summary->missed_count;

        $attendanceBase = $doneCount + $missedCount;
        $attendanceRate = $attendanceBase > 0 ? $doneCount / $attendanceBase : null;
        $avgSessionsPerPatient = $uniquePatients > 0 ? round($totalSessions / $uniquePatients, 2) : 0;
        $avgTicketValue = $paidCount > 0 ? $paidValue / $paidCount : 0;

        return response()->json([
            'filters' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'payments' => [
                'paid' => [
                    'appointments' => $paidCount,
                    'value' => $this->formatMoney($paidValue),
                ],
                'pending' => [
                    'appointments' => $pendingCount,
                    'value' => $this->formatMoney($pendingValue),
                ],
            ],
            'appointments' => [
                'done' => ['count' => $doneCount],
                'canceled' => ['count' => $canceledCount],
                'missed' => ['count' => $missedCount],
            ],
            'summary' => [
                'attendance_rate' => $attendanceRate,
                'avg_sessions_per_patient' => $avgSessionsPerPatient,
                'avg_ticket' => $this->formatMoney($avgTicketValue),
                'total_sessions' => $totalSessions,
                'unique_patients' => $uniquePatients,
            ],
            'lists' => [
                'paid' => $paidList,
                'pending' => $pendingList,
                'done' => $doneList,
                'canceled' => $canceledList,
                'missed' => $missedList,
            ],
        ]);
    }

    private function psychologistId(Request $request): int
    {
        $user = $request->user()->loadMissing('psychologist');

        $psychologistId = $user->psychologist?->id;

        abort_if(
            ! $psychologistId,
            403,
            'Usuário autenticado não possui um perfil de psicólogo.'
        );

        return (int) $psychologistId;
    }

    private function resolveRange(Request $request): array
    {
        $from = $this->parseDate($request->string('from')->toString() ?: null, false);
        $to = $this->parseDate($request->string('to')->toString() ?: null, true);

        return [$from, $to];
    }

    private function parseDate(?string $value, bool $endOfDay): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value);

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function formatMoney(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /** @param Builder<Appointment> $query */
    private function formatAppointmentsForReport(Builder $query): array
    {
        return $query
            ->with('patient:id,name')
            ->orderByDesc('start_at')
            ->get()
            ->map(function (Appointment $appointment) {
                return [
                    'id' => $appointment->id,
                    'patient' => [
                        'id' => $appointment->patient_id,
                        'name' => $appointment->patient->name,
                    ],
                    'start_at' => $appointment->start_at->toIso8601String(),
                    'end_at' => $appointment->end_at->toIso8601String(),
                    'status' => $appointment->status,
                    'price' => $this->formatMoney($appointment->price),
                    'paid_at' => $appointment->paid_at?->toIso8601String(),
                ];
            })
            ->all();
    }
}
