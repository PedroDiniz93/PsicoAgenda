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

        $paidValue = (clone $paidBuilder)->sum('price');
        $paidCount = (clone $paidBuilder)->count();
        $pendingValue = (clone $pendingBuilder)->sum('price');
        $pendingCount = (clone $pendingBuilder)->count();

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

        $doneCount = count($doneList);
        $canceledCount = count($canceledList);
        $missedCount = count($missedList);

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
            !$psychologistId,
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
        if (!$value) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value);

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function formatMoney(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

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
                        'name' => $appointment->patient?->name,
                    ],
                    'start_at' => $appointment->start_at?->toIso8601String(),
                    'end_at' => $appointment->end_at?->toIso8601String(),
                    'status' => $appointment->status,
                    'price' => $this->formatMoney($appointment->price),
                    'paid_at' => $appointment->paid_at?->toIso8601String(),
                ];
            })
            ->all();
    }
}
