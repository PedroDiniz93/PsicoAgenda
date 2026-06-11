<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Psychologist;
use App\Models\PsychologistAvailabilityRule;
use App\Models\PsychologistScheduleBlock;
use Illuminate\Support\Carbon;

class AppointmentAvailabilityService
{
    public function ensureCanSchedule(Psychologist $psychologist, Carbon $start, Carbon $end, ?int $ignoreAppointmentId = null): void
    {
        $message = $this->firstSchedulingConflict($psychologist, $start, $end, $ignoreAppointmentId);

        abort_if($message !== null, 422, $message);
    }

    public function firstSchedulingConflict(Psychologist $psychologist, Carbon $start, Carbon $end, ?int $ignoreAppointmentId = null): ?string
    {
        $timezone = $psychologist->timezone ?? config('app.timezone');
        $startLocal = $start->copy()->setTimezone($timezone);
        $endLocal = $end->copy()->setTimezone($timezone);

        if (!$startLocal->isSameDay($endLocal)) {
            return 'Agendamentos devem começar e terminar no mesmo dia de atendimento.';
        }

        if (!$this->isWithinAvailability($psychologist, $startLocal, $endLocal)) {
            return 'Horário fora da disponibilidade configurada.';
        }

        if ($this->hasScheduleBlock($psychologist, $start, $end)) {
            return 'Esse horário está bloqueado na disponibilidade do psicólogo.';
        }

        if ($this->exceedsDailyLimit($psychologist, $startLocal, $ignoreAppointmentId)) {
            return 'Limite diário de atendimentos atingido para essa data.';
        }

        return null;
    }

    private function isWithinAvailability(Psychologist $psychologist, Carbon $startLocal, Carbon $endLocal): bool
    {
        $rules = PsychologistAvailabilityRule::query()
            ->where('psychologist_id', $psychologist->id)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            return true;
        }

        $weekday = (int) $startLocal->dayOfWeek;
        $startTime = $startLocal->format('H:i:s');
        $endTime = $endLocal->format('H:i:s');

        return $rules
            ->where('weekday', $weekday)
            ->contains(function (PsychologistAvailabilityRule $rule) use ($startTime, $endTime) {
                return $rule->start_time <= $startTime && $rule->end_time >= $endTime;
            });
    }

    private function hasScheduleBlock(Psychologist $psychologist, Carbon $start, Carbon $end): bool
    {
        return PsychologistScheduleBlock::query()
            ->where('psychologist_id', $psychologist->id)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    private function exceedsDailyLimit(Psychologist $psychologist, Carbon $startLocal, ?int $ignoreAppointmentId = null): bool
    {
        $limit = (int) ($psychologist->daily_appointment_limit ?? 0);
        if ($limit <= 0) {
            return false;
        }

        $timezone = $psychologist->timezone ?? config('app.timezone');
        $dayStart = $startLocal->copy()->startOfDay()->timezone(config('app.timezone'));
        $dayEnd = $startLocal->copy()->endOfDay()->timezone(config('app.timezone'));

        $query = Appointment::query()
            ->where('psychologist_id', $psychologist->id)
            ->where('status', '!=', 'canceled')
            ->whereBetween('start_at', [$dayStart, $dayEnd]);

        if ($ignoreAppointmentId) {
            $query->where('id', '!=', $ignoreAppointmentId);
        }

        return $query->count() >= $limit;
    }
}
