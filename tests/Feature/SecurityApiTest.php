<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientRecord;
use App\Models\Psychologist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_webhook_rejects_invalid_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'test-app-secret']);
        $payload = ['object' => 'whatsapp_business_account', 'entry' => []];
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], $rawPayload)->assertUnauthorized();
    }

    public function test_whatsapp_webhook_accepts_a_valid_signed_payload(): void
    {
        config(['services.whatsapp.app_secret' => 'test-app-secret']);
        $payload = ['object' => 'whatsapp_business_account', 'entry' => []];
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $rawPayload, 'test-app-secret');

        $this->call('POST', '/api/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $rawPayload)->assertOk()->assertJson(['success' => true]);
    }

    public function test_twilio_whatsapp_webhook_rejects_invalid_signature(): void
    {
        config(['services.twilio.auth_token' => 'test-twilio-auth-token']);

        $this->post('/api/webhook/whatsapp', [
            'Body' => 'ok',
            'From' => 'whatsapp:+55119999999999',
        ], [
            'X-Twilio-Signature' => 'invalid',
        ])->assertUnauthorized();
    }

    public function test_twilio_whatsapp_webhook_accepts_a_valid_signed_payload(): void
    {
        $authToken = 'test-twilio-auth-token';
        config(['services.twilio.auth_token' => $authToken]);
        $payload = [
            'Body' => 'ok',
            'From' => 'whatsapp:+55119999999999',
        ];
        $signatureData = url('/api/webhook/whatsapp');
        ksort($payload);
        foreach ($payload as $key => $value) {
            $signatureData .= $key.$value;
        }
        $signature = base64_encode(hash_hmac('sha1', $signatureData, $authToken, true));

        $this->post('/api/webhook/whatsapp', $payload, [
            'X-Twilio-Signature' => $signature,
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_patient_record_attachment_requires_the_patient_owner(): void
    {
        Storage::fake('private');
        [$user, $psychologist] = $this->psychologist();
        $patient = Patient::factory()->create(['psychologist_id' => $psychologist->id]);
        $path = "patient-records/{$patient->id}/record.pdf";
        Storage::disk('private')->put($path, 'private clinical content');
        $record = PatientRecord::create([
            'psychologist_id' => $psychologist->id,
            'patient_id' => $patient->id,
            'recorded_at' => now(),
            'title' => 'Sessão',
            'notes' => 'Anexo privado',
            'attachments' => [[
                'id' => 'attachment-1',
                'name' => 'record.pdf',
                'mime_type' => 'application/pdf',
                'size' => 24,
                'path' => $path,
                'disk' => 'private',
            ]],
        ]);
        Sanctum::actingAs($user);

        $this->get("/api/patients/{$patient->id}/records/{$record->id}/attachments/attachment-1")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        [, $otherPsychologist] = $this->psychologist();
        $otherPatient = Patient::factory()->create(['psychologist_id' => $otherPsychologist->id]);
        $this->get("/api/patients/{$otherPatient->id}/records/{$record->id}/attachments/attachment-1")
            ->assertNotFound();
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
