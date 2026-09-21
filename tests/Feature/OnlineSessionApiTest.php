<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnlineSessionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_psychologist_can_create_a_temporary_online_session(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $appointment = $this->appointment($psychologist, $patient);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/appointments/{$appointment->id}/online-session");

        $response->assertCreated()
            ->assertJsonStructure(['id', 'appointment_id', 'patient_id', 'status', 'expires_at', 'patient_token'])
            ->assertJsonPath('status', 'waiting');

        $this->assertDatabaseHas('online_sessions', [
            'psychologist_id' => $psychologist->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'status' => 'waiting',
        ]);
    }

    public function test_a_patient_link_exposes_only_public_room_data(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $appointment = $this->appointment($psychologist, $patient);
        Sanctum::actingAs($user);

        $creation = $this->postJson("/api/appointments/{$appointment->id}/online-session")->assertCreated();
        $token = $creation->json('patient_token');

        $this->getJson("/api/online-sessions/join/{$token}")
            ->assertOk()
            ->assertJsonStructure(['id', 'status', 'expires_at'])
            ->assertJsonMissing(['patient_id', 'appointment_id', 'name', 'email', 'notes', 'token']);
    }

    public function test_a_psychologist_cannot_create_a_session_for_another_psychologist(): void
    {
        [$user] = $this->psychologist();
        [, $otherPsychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $otherPsychologist->id]);
        $appointment = $this->appointment($otherPsychologist, $patient);
        Sanctum::actingAs($user);

        $this->postJson("/api/appointments/{$appointment->id}/online-session")
            ->assertNotFound();
    }

    public function test_ended_room_cannot_be_joined(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $appointment = $this->appointment($psychologist, $patient);
        Sanctum::actingAs($user);

        $creation = $this->postJson("/api/appointments/{$appointment->id}/online-session")->assertCreated();
        $sessionId = $creation->json('id');
        $token = $creation->json('patient_token');

        $this->postJson("/api/online-sessions/{$sessionId}/end")->assertOk();
        $this->getJson("/api/online-sessions/join/{$token}")
            ->assertStatus(410)
            ->assertJsonPath('message', 'Esta sala não está disponível.');
    }

    public function test_patient_entry_request_waits_for_psychologist_approval(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $appointment = $this->appointment($psychologist, $patient);
        Sanctum::actingAs($user);

        $creation = $this->postJson("/api/appointments/{$appointment->id}/online-session")->assertCreated();
        $sessionId = $creation->json('id');
        $token = $creation->json('patient_token');

        $this->postJson("/api/online-sessions/join/{$token}/signal", [
            'type' => 'presence',
            'payload' => [
                'role' => 'patient',
                'state' => 'requesting',
                'connection_id' => 'patient-connection-0001',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('online_sessions', [
            'id' => $sessionId,
            'status' => 'waiting',
        ]);

        $this->getJson("/api/online-sessions/{$sessionId}")
            ->assertOk()
            ->assertJsonPath('patient_waiting_for_approval', true);
    }

    public function test_only_psychologist_can_approve_patient_entry(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $appointment = $this->appointment($psychologist, $patient);
        Sanctum::actingAs($user);

        $creation = $this->postJson("/api/appointments/{$appointment->id}/online-session")->assertCreated();
        $sessionId = $creation->json('id');
        $token = $creation->json('patient_token');

        $this->postJson("/api/online-sessions/join/{$token}/signal", [
            'type' => 'entry-approved',
            'payload' => ['role' => 'patient'],
        ])->assertForbidden();

        $this->postJson("/api/online-sessions/{$sessionId}/signal", [
            'type' => 'entry-approved',
            'payload' => ['role' => 'psychologist'],
        ])->assertOk();
    }

    public function test_patient_can_confirm_approval_from_the_public_room_state(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $appointment = $this->appointment($psychologist, $patient);
        Sanctum::actingAs($user);

        $creation = $this->postJson("/api/appointments/{$appointment->id}/online-session")->assertCreated();
        $sessionId = $creation->json('id');
        $token = $creation->json('patient_token');

        $this->postJson("/api/online-sessions/join/{$token}/signal", [
            'type' => 'presence',
            'payload' => [
                'role' => 'patient',
                'state' => 'requesting',
                'connection_id' => 'patient-connection-0001',
            ],
        ])->assertOk();

        $this->postJson("/api/online-sessions/{$sessionId}/signal", [
            'type' => 'entry-approved',
            'payload' => ['role' => 'psychologist'],
        ])->assertOk();

        Sanctum::actingAs($user);
        $this->getJson("/api/online-sessions/join/{$token}")
            ->assertOk()
            ->assertJsonPath('entry_approved', true);
    }

    public function test_a_second_patient_connection_is_rejected_for_the_same_link(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $appointment = $this->appointment($psychologist, $patient);
        Sanctum::actingAs($user);

        $creation = $this->postJson("/api/appointments/{$appointment->id}/online-session")->assertCreated();
        $token = $creation->json('patient_token');

        $payload = fn (string $connectionId) => [
            'type' => 'presence',
            'payload' => [
                'role' => 'patient',
                'state' => 'requesting',
                'connection_id' => $connectionId,
            ],
        ];

        $this->postJson("/api/online-sessions/join/{$token}/signal", $payload('patient-connection-0001'))
            ->assertOk();

        $this->postJson("/api/online-sessions/join/{$token}/signal", $payload('patient-connection-0002'))
            ->assertStatus(409)
            ->assertJsonPath('message', 'Esta sala já está sendo usada por outro paciente.');
    }

    public function test_ice_server_endpoint_does_not_expose_twilio_credentials(): void
    {
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $appointment = $this->appointment($psychologist, $patient);
        Sanctum::actingAs($user);

        $creation = $this->postJson("/api/appointments/{$appointment->id}/online-session")->assertCreated();
        $sessionId = $creation->json('id');
        $token = $creation->json('patient_token');

        $this->getJson("/api/online-sessions/{$sessionId}/ice-servers")
            ->assertOk()
            ->assertJsonStructure(['ice_servers'])
            ->assertJsonMissing(['account_sid', 'api_key', 'api_secret', 'auth_token']);

        $this->getJson("/api/online-sessions/join/{$token}/ice-servers")
            ->assertOk()
            ->assertJsonStructure(['ice_servers'])
            ->assertJsonMissing(['account_sid', 'api_key', 'api_secret', 'auth_token']);
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

    private function appointment(Psychologist $psychologist, Patient $patient): Appointment
    {
        return Appointment::create([
            'psychologist_id' => $psychologist->id,
            'patient_id' => $patient->id,
            'start_at' => '2026-07-15 10:00:00',
            'end_at' => '2026-07-15 10:50:00',
            'status' => 'scheduled',
            'type' => 'online',
        ]);
    }
}
