<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'psicologo@example.com',
            'password' => bcrypt('senha-segura'),
            'role' => 'psychologist',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'senha-segura',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user', 'requires_email_verification', 'remember'])
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('requires_email_verification', false);
    }

    public function test_it_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'psicologo@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'senha-incorreta',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Credenciais inválidas.');
    }

    public function test_it_requires_authentication_for_me(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_unverified_psychologists_are_flagged_at_login(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'role' => 'psychologist',
            'email' => 'pendente@example.com',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('requires_email_verification', true);

        Mail::assertSentCount(1);
    }
}
