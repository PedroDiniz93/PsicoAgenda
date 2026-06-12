<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_patient_metrics_for_the_authenticated_psychologist(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-12 09:00:00', 'America/Sao_Paulo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'psychologist',
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

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'psychologist',
        ]);

        $otherPsychologist = Psychologist::create([
            'user_id' => $otherUser->id,
            'name' => $otherUser->name,
            'email' => $otherUser->email,
            'timezone' => 'America/Sao_Paulo',
            'session_duration' => 50,
            'allow_online' => true,
            'allow_in_person' => true,
        ]);

        Patient::factory()->create([
            'psychologist_id' => $psychologist->id,
            'status' => 'active',
            'created_at' => Carbon::parse('2026-06-03 10:00:00', 'America/Sao_Paulo'),
        ]);

        Patient::factory()->create([
            'psychologist_id' => $psychologist->id,
            'status' => 'active',
            'created_at' => Carbon::parse('2026-06-08 10:00:00', 'America/Sao_Paulo'),
        ]);

        Patient::factory()->create([
            'psychologist_id' => $psychologist->id,
            'status' => 'paused',
            'created_at' => Carbon::parse('2026-06-10 10:00:00', 'America/Sao_Paulo'),
        ]);

        Patient::factory()->create([
            'psychologist_id' => $psychologist->id,
            'status' => 'closed',
            'created_at' => Carbon::parse('2026-05-20 10:00:00', 'America/Sao_Paulo'),
        ]);

        Patient::factory()->create([
            'psychologist_id' => $otherPsychologist->id,
            'status' => 'active',
            'created_at' => Carbon::parse('2026-06-05 10:00:00', 'America/Sao_Paulo'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/patients?status=active&per_page=1');

        $response->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('metrics.total', 4)
            ->assertJsonPath('metrics.created_this_month', 3)
            ->assertJsonPath('metrics.active', 2)
            ->assertJsonPath('metrics.paused', 1)
            ->assertJsonPath('metrics.closed', 1);

        Carbon::setTestNow();
    }
}
