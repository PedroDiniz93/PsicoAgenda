<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\PatientAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GeneratePatientInactivityAlerts extends Command
{
    protected $signature = 'alerts:generate-inactivity {--days=15}';

    protected $description = 'Gera alertas para pacientes sem agendamentos recentes.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $days = max(1, min($days, 365));

        $thresholdDate = Carbon::now()->subDays($days);
        $now = Carbon::now();

        $createdCount = 0;
        $activeAlertIds = [];

        Patient::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'closed');
            })
            ->withMax('appointments as last_appointment_at', 'start_at')
            ->orderBy('id')
            ->chunkById(200, function ($patients) use (&$createdCount, &$activeAlertIds, $thresholdDate, $now, $days) {
                foreach ($patients as $patient) {
                    $lastAppointment = $patient->last_appointment_at
                        ? Carbon::parse($patient->last_appointment_at)
                        : null;

                    $referenceDate = $lastAppointment ?? $patient->created_at;

                    if (!$referenceDate instanceof Carbon || $referenceDate->gte($thresholdDate)) {
                        continue;
                    }

                    $daysSince = $referenceDate->diffInDays($now);

                    $payload = [
                        'threshold_days' => $days,
                        'days_since_last_appointment' => $daysSince,
                        'last_appointment_at' => $lastAppointment?->toIso8601String(),
                        'reference_date' => $referenceDate->toIso8601String(),
                    ];

                    $alert = PatientAlert::firstOrNew([
                        'psychologist_id' => $patient->psychologist_id,
                        'patient_id' => $patient->id,
                        'type' => 'patient-inactivity',
                    ]);

                    $existingPayload = is_array($alert->payload) ? $alert->payload : [];
                    $existingReference = $existingPayload['reference_date'] ?? null;
                    $isNewCycle = $existingReference !== $payload['reference_date'];

                    if (!$alert->exists || $isNewCycle) {
                        $alert->resolved_at = null;
                    }

                    if (!$alert->exists) {
                        $createdCount++;
                    }

                    $alert->fill([
                        'title' => 'Paciente sem agendamento recente',
                        'message' => sprintf(
                            '%s está há %d dia(s) sem novos agendamentos.',
                            $patient->name ?: 'Paciente sem nome',
                            $daysSince
                        ),
                        'payload' => $payload,
                        'triggered_at' => $now,
                    ]);

                    $alert->save();

                    $activeAlertIds[] = $alert->id;
                }
            });

        if (!empty($activeAlertIds)) {
            PatientAlert::where('type', 'patient-inactivity')
                ->whereNotIn('id', $activeAlertIds)
                ->delete();
        } else {
            PatientAlert::where('type', 'patient-inactivity')->delete();
        }

        $message = sprintf('Foram gerados %d alertas de pacientes sem agendamento recente (>%d dias).', $createdCount, $days);
        $this->info($message);
        Log::info($message);

        return self::SUCCESS;
    }
}
