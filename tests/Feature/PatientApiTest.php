<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_psychologist_can_create_update_and_close_a_patient(): void
    {
        [$user, $psychologist] = $this->psychologist();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/patients', [
            'name' => 'Paciente Novo',
            'email' => 'paciente@example.com',
            'status' => 'active',
        ]);

        $create->assertCreated()->assertJsonPath('name', 'Paciente Novo');
        $patientId = $create->json('id');

        $this->assertDatabaseHas('patients', [
            'id' => $patientId,
            'psychologist_id' => $psychologist->id,
        ]);

        $this->putJson("/api/patients/{$patientId}", ['name' => 'Paciente Atualizado'])
            ->assertOk()
            ->assertJsonPath('name', 'Paciente Atualizado');

        $this->deleteJson("/api/patients/{$patientId}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('patients', ['id' => $patientId, 'status' => 'closed']);
    }

    public function test_patient_data_is_isolated_between_psychologists(): void
    {
        [$user] = $this->psychologist();
        [, $otherPsychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $otherPsychologist->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/patients/{$patient->id}")->assertNotFound();
        $this->putJson("/api/patients/{$patient->id}", ['name' => 'Acesso indevido'])->assertNotFound();
        $this->deleteJson("/api/patients/{$patient->id}")->assertNotFound();
    }

    public function test_patient_creation_validates_required_name(): void
    {
        [$user] = $this->psychologist();
        Sanctum::actingAs($user);

        $this->postJson('/api/patients', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @return array{0: User, 1: Psychologist} */
    private function psychologist(): array
    {
        $user = User::factory()->create([
            'role' => 'psychologist',
            'email_verified_at' => now(),
        ]);

        $psychologist = Psychologist::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => 'America/Sao_Paulo',
            'session_duration' => 50,
            'allow_online' => true,
            'allow_in_person' => true,
        ]);

        return [$user, $psychologist];
    }
}
