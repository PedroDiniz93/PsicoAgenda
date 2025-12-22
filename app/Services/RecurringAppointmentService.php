<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\RecurringAppointment;
use Illuminate\Support\Carbon;

class RecurringAppointmentService
{
    private const WEEKS_AHEAD = 8;

    public function generateUpcomingOccurrences(RecurringAppointment $recurrence, ?Carbon $until = null): void
    {
        if ($recurrence->status !== 'active') {
            return;
        }

        $timezone = $recurrence->timezone ?? config('app.timezone');
        $reference = Carbon::now($timezone)->startOfDay();
        $cursor = $this->resolveNextDate($recurrence, $reference);

        if ($cursor === null) {
            return;
        }

        $limit = $until ?: $reference->copy()->addWeeks(self::WEEKS_AHEAD);
        if ($recurrence->end_date) {
            $endDate = Carbon::parse($recurrence->end_date, $timezone)->startOfDay();
            if ($endDate->lt($limit)) {
                $limit = $endDate;
            }
        }

        while ($cursor->lte($limit)) {
            $this->createOccurrence($recurrence, $cursor->copy(), $timezone);
            $cursor->addWeek();
        }
    }

    public function cancelFutureOccurrences(RecurringAppointment $recurrence): int
    {
        $nowUtc = Carbon::now(config('app.timezone'));

        return $recurrence->appointments()
            ->where('start_at', '>=', $nowUtc)
            ->where('status', 'scheduled')
            ->update(['status' => 'canceled']);
    }

    private function resolveNextDate(RecurringAppointment $recurrence, Carbon $reference): ?Carbon
    {
        $timezone = $recurrence->timezone ?? config('app.timezone');
        $startDate = Carbon::parse($recurrence->start_date, $timezone)->startOfDay();
        $referenceDate = $reference->copy()->startOfDay();

        if ($recurrence->end_date) {
            $endDate = Carbon::parse($recurrence->end_date, $timezone)->startOfDay();
            if ($referenceDate->gt($endDate)) {
                return null;
            }
        }

        if ($referenceDate->lte($startDate)) {
            return $startDate;
        }

        $diffDays = $startDate->diffInDays($referenceDate);
        $weeks = intdiv($diffDays, 7);
        $candidate = $startDate->copy()->addWeeks($weeks);

        while ($candidate->lt($referenceDate)) {
            $candidate->addWeek();
        }

        return $candidate;
    }

    private function createOccurrence(RecurringAppointment $recurrence, Carbon $date, string $timezone): void
    {
        if ($recurrence->end_date && $date->gt(Carbon::parse($recurrence->end_date, $timezone))) {
            return;
        }

        $occursAt = $date->toDateString();

        if (Appointment::where('recurrence_id', $recurrence->id)->whereDate('occurrence_date', $occursAt)->exists()) {
            return;
        }

        $start = $this->buildDateTime($occursAt, $recurrence->start_time, $timezone);
        $end = $start->copy()->addMinutes($recurrence->session_duration);

        if ($this->hasOverlap($recurrence->psychologist_id, $start, $end)) {
            return;
        }

        Appointment::create([
            'psychologist_id' => $recurrence->psychologist_id,
            'patient_id' => $recurrence->patient_id,
            'recurrence_id' => $recurrence->id,
            'occurrence_date' => $occursAt,
            'start_at' => $start,
            'end_at' => $end,
            'status' => 'scheduled',
            'type' => $recurrence->type,
            'price' => $recurrence->price,
        ]);
    }

    private function buildDateTime(string $date, string $time, string $timezone): Carbon
    {
        return Carbon::parse(sprintf('%s %s', $date, $time), $timezone)->timezone(config('app.timezone'));
    }

    private function hasOverlap(int $psychologistId, Carbon $start, Carbon $end): bool
    {
        return Appointment::where('psychologist_id', $psychologistId)
            ->where('status', '!=', 'canceled')
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();
    }
}
