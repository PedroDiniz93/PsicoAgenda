<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\Psychologist;
use App\Models\User;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@teste.com'],
            [
                'name' => 'Psicólogo Admin',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        Psychologist::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Psicólogo Admin',
                'phone' => null,
                'email' => 'admin@teste.com',
                'timezone' => 'America/Sao_Paulo',
                'session_duration' => 50,
                'allow_online' => true,
                'allow_in_person' => true,
            ]
        );

        $psychologist = $user->psychologist()->firstOrFail();

        if ($psychologist->patients()->count() < 10) {
            Patient::factory()
                ->count(10)
                ->create([
                    'psychologist_id' => $psychologist->id,
                    'status' => 'active',
                ]);
        }

        $patients = $psychologist->patients()->orderBy('id')->get();

        if ($patients->isEmpty()) {
            $patients = Patient::factory()
                ->count(10)
                ->create([
                    'psychologist_id' => $psychologist->id,
                    'status' => 'active',
                ]);
        }

        $this->seedWeeklyAppointments($psychologist, $patients);
        $this->seedUnreadAlerts($psychologist, $patients);
    }

    private function seedWeeklyAppointments(Psychologist $psychologist, Collection $patients): void
    {
        $timezone = $psychologist->timezone ?? config('app.timezone');
        $sessionDuration = $psychologist->session_duration ?? 50;

        $now = Carbon::now($timezone);
        $startOfToday = $now->copy()->startOfDay();
        $slotsPerDay = 2;
        $baseHour = 9;

        for ($day = 0; $day < 7; $day++) {
            for ($slot = 0; $slot < $slotsPerDay; $slot++) {
                $start = $startOfToday->copy()
                    ->addDays($day)
                    ->setTime($baseHour + ($slot * 2), 0);
                $end = $start->copy()->addMinutes($sessionDuration);

                $patient = $patients[$day * $slotsPerDay + $slot] ?? $patients->random();

                Appointment::updateOrCreate(
                    [
                        'psychologist_id' => $psychologist->id,
                        'patient_id' => $patient->id,
                        'start_at' => $start,
                    ],
                    [
                        'end_at' => $end,
                        'status' => 'scheduled',
                        'type' => 'in_person',
                        'price' => 200,
                    ]
                );
            }
        }
    }

    private function seedUnreadAlerts(Psychologist $psychologist, Collection $patients): void
    {
        $now = Carbon::now();
        $inactivePatients = $patients->take(5);

        foreach ($inactivePatients as $index => $patient) {
            $daysSince = 15 + ($index * 3);
            $referenceDate = $now->copy()->subDays($daysSince);
            $lastAppointment = $referenceDate->copy()->subDays(2);

            PatientAlert::updateOrCreate(
                [
                    'psychologist_id' => $psychologist->id,
                    'patient_id' => $patient->id,
                    'type' => 'patient-inactivity',
                ],
                [
                    'title' => 'Paciente sem agendamento recente',
                    'message' => sprintf(
                        '%s está há %d dia(s) sem novos agendamentos.',
                        $patient->name ?: 'Paciente sem nome',
                        $daysSince
                    ),
                    'payload' => [
                        'threshold_days' => 15,
                        'days_since_last_appointment' => $daysSince,
                        'last_appointment_at' => $lastAppointment->toIso8601String(),
                        'reference_date' => $referenceDate->toIso8601String(),
                    ],
                    'triggered_at' => $now,
                    'resolved_at' => null,
                ]
            );
        }
    }
}
