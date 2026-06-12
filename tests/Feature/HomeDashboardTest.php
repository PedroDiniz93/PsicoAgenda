<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_dashboard_sections_for_the_authenticated_psychologist(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-12 09:00:00', 'America/Sao_Paulo'));

        $user = User::factory()->create([
            'name' => 'Psicóloga Teste',
            'email' => 'teste@example.com',
            'email_verified_at' => now(),
            'role' => 'psychologist',
        ]);

        $psychologist = Psychologist::create([
            'user_id' => $user->id,
            'name' => 'Psicóloga Teste',
            'email' => 'teste@example.com',
            'timezone' => 'America/Sao_Paulo',
            'session_duration' => 50,
            'allow_online' => true,
            'allow_in_person' => true,
        ]);

        $patient = Patient::factory()->create([
            'psychologist_id' => $psychologist->id,
            'name' => 'Paciente Exemplo',
            'status' => 'active',
        ]);

        Appointment::create([
            'psychologist_id' => $psychologist->id,
            'patient_id' => $patient->id,
            'start_at' => Carbon::parse('2026-06-12 10:00:00', 'America/Sao_Paulo'),
            'end_at' => Carbon::parse('2026-06-12 10:50:00', 'America/Sao_Paulo'),
            'status' => 'scheduled',
            'type' => 'in_person',
            'price' => 200,
        ]);

        Appointment::create([
            'psychologist_id' => $psychologist->id,
            'patient_id' => $patient->id,
            'start_at' => Carbon::parse('2026-06-10 11:00:00', 'America/Sao_Paulo'),
            'end_at' => Carbon::parse('2026-06-10 11:50:00', 'America/Sao_Paulo'),
            'status' => 'done',
            'type' => 'online',
            'price' => 220,
            'paid_at' => Carbon::parse('2026-06-10 12:00:00', 'America/Sao_Paulo'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/home/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'hero' => ['kicker', 'title', 'description'],
                'metrics' => [
                    ['id', 'label', 'value', 'detail', 'icon', 'accent'],
                ],
                'next_patients' => [
                    ['id', 'patient', 'time_label', 'modality_label', 'status_label', 'status_accent'],
                ],
                'weekly_attendances' => [
                    'label',
                    'from',
                    'to',
                    'total',
                    'average_daily',
                    'days' => [
                        ['key', 'label', 'full_label', 'count', 'ratio'],
                    ],
                ],
            ]);

        $response->assertJsonPath('next_patients.0.patient.name', 'Paciente Exemplo');
        $response->assertJsonPath('weekly_attendances.total', 1);

        Carbon::setTestNow();
    }
}
