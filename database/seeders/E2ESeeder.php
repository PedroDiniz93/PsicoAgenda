<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2ESeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'e2e@psicoagenda.test'],
            [
                'name' => 'Psicóloga E2E',
                'password' => Hash::make('password'),
                'role' => 'psychologist',
                'email_verified_at' => now(),
            ]
        );

        $psychologist = Psychologist::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Psicóloga E2E',
                'email' => $user->email,
                'timezone' => 'America/Sao_Paulo',
            ]
        );

        $patient = Patient::updateOrCreate(
            ['psychologist_id' => $psychologist->id, 'email' => 'paciente@psicoagenda.test'],
            [
                'name' => 'Paciente E2E',
                'phone' => '(11) 99999-0000',
                'status' => 'active',
                'session_fee_type' => 'session',
                'session_fee_value' => 180,
            ]
        );

        $appointmentStart = now()->startOfDay()->setTime(14, 0);

        Appointment::updateOrCreate(
            [
                'psychologist_id' => $psychologist->id,
                'patient_id' => $patient->id,
                'start_at' => $appointmentStart,
            ],
            [
                'end_at' => $appointmentStart->copy()->addMinutes(50),
                'status' => 'scheduled',
                'type' => 'online',
                'price' => 180,
                'payment_due_at' => $appointmentStart->toDateString(),
            ]
        );
    }
}
