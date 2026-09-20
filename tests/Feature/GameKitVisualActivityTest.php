<?php

namespace Tests\Feature;

use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GameKitVisualActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_psychologist_can_generate_and_publish_a_visual_activity(): void
    {
        [$user, $psychologist] = $this->psychologist();
        Sanctum::actingAs($user);
        Storage::fake('public');
        Http::fake([
            'https://api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => base64_encode(str_repeat('png-data', 30))]],
            ]),
        ]);

        $response = $this->postJson('/api/gamekit/visual-activities/generate', [
            'type' => 'coloring_cutting',
            'style' => 'intermediate',
            'theme' => 'Animais da floresta',
            'therapeutic_goal' => 'Nomear emoções e praticar escolhas',
        ]);

        $response->assertCreated()->assertJsonPath('activity.status', 'ready')->assertJsonPath('quota.remaining', 4);
        $activityId = $response->json('activity.id');
        $activity = \App\Models\GameKitVisualActivity::findOrFail($activityId);
        Storage::disk('public')->assertExists($activity->storage_path);

        $this->postJson("/api/gamekit/visual-activities/{$activityId}/publish")
            ->assertOk()
            ->assertJsonStructure(['url', 'expires_at']);
        $this->assertDatabaseHas('gamekit_visual_activities', [
            'id' => $activityId,
            'psychologist_id' => $psychologist->id,
            'public_status' => 'active',
        ]);
    }

    public function test_the_weekly_limit_is_enforced_without_crossing_psychologists(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        [$user] = $this->psychologist();
        Sanctum::actingAs($user);
        Storage::fake('public');
        Http::fake([
            'https://api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => base64_encode(str_repeat('png-data', 30))]],
            ]),
        ]);

        $payload = [
            'type' => 'coloring',
            'style' => 'simple',
            'theme' => 'Formas da natureza',
            'therapeutic_goal' => 'Explorar escolhas',
        ];
        foreach (range(1, 5) as $_) {
            $this->postJson('/api/gamekit/visual-activities/generate', $payload)->assertCreated();
        }

        $this->postJson('/api/gamekit/visual-activities/generate', $payload)
            ->assertStatus(429)
            ->assertJsonPath('message', 'O limite de 5 imagens por semana foi atingido.');
    }

    public function test_sensitive_data_is_rejected_from_the_generation_request(): void
    {
        [$user] = $this->psychologist();
        Sanctum::actingAs($user);

        $this->postJson('/api/gamekit/visual-activities/generate', [
            'type' => 'coloring',
            'style' => 'simple',
            'theme' => 'Animais',
            'therapeutic_goal' => 'Paciente teste@example.com',
        ])->assertStatus(422);
    }

    /** @return array{0: User, 1: Psychologist} */
    private function psychologist(): array
    {
        config(['services.openai.api_key' => 'test-key']);
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
