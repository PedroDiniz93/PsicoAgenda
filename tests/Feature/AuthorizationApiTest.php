<?php

namespace Tests\Feature;

use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_psychologist_cannot_access_protected_api(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'psychologist']);
        Psychologist::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/patients')->assertForbidden()
            ->assertJsonPath('requires_email_verification', true);
    }

    public function test_authenticated_user_without_psychologist_profile_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'psychologist',
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/patients')->assertForbidden()
            ->assertJsonPath('message', 'Usuário autenticado não possui um perfil de psicólogo.');
    }

    public function test_only_admins_can_access_admin_api(): void
    {
        $psychologist = User::factory()->create([
            'role' => 'psychologist',
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($psychologist);

        $this->getJson('/api/admin/psychologists')->assertForbidden();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/psychologists')->assertOk()
            ->assertJsonStructure(['psychologists']);
    }
}
