<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_psychologist_can_schedule_an_appointment_for_owned_patient(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'start_at' => '2026-07-15 10:00:00',
            'end_at' => '2026-07-15 10:50:00',
            'type' => 'online',
            'price' => 150,
        ]);

        $response->assertCreated()->assertJsonPath('patient_id', $patient->id);
        $this->assertDatabaseHas('appointments', [
            'psychologist_id' => $psychologist->id,
            'patient_id' => $patient->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_it_rejects_overlapping_appointments(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        Appointment::create([
            'psychologist_id' => $psychologist->id,
            'patient_id' => $patient->id,
            'start_at' => '2026-07-15 10:00:00',
            'end_at' => '2026-07-15 10:50:00',
            'status' => 'scheduled',
            'type' => 'online',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'start_at' => '2026-07-15 10:30:00',
            'end_at' => '2026-07-15 11:20:00',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Já existe um agendamento nesse intervalo de horário.');
    }

    public function test_an_appointment_for_another_psychologist_is_not_exposed(): void
    {
        [$user] = $this->psychologist();
        [, $otherPsychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $otherPsychologist->id]);
        $appointment = Appointment::create([
            'psychologist_id' => $otherPsychologist->id,
            'patient_id' => $patient->id,
            'start_at' => '2026-07-15 10:00:00',
            'end_at' => '2026-07-15 10:50:00',
            'status' => 'scheduled',
            'type' => 'online',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/appointments')->assertOk()->assertJsonMissing(['id' => $appointment->id]);
        $this->deleteJson("/api/appointments/{$appointment->id}")->assertNotFound();
    }

    /** @return array{0: User, 1: Psychologist} */
    private function psychologist(): array
    {
        $user = User::factory()->create([
            'role' => 'psychologist',
            'email_verified_at' => now(),
        ]);

        return [$user, Psychologist::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => 'America/Sao_Paulo',
            'session_duration' => 50,
            'allow_online' => true,
            'allow_in_person' => true,
        ])];
    }
}
