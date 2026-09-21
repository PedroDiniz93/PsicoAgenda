<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response|JsonResponse
    {
        $verifyToken = config('services.whatsapp.verify_token');
        $mode = $request->string('hub_mode')->toString();
        $token = $request->string('hub_verify_token')->toString();
        $challenge = $request->string('hub_challenge')->toString();

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json('Invalid token', 403);
    }

    public function receive(Request $request): JsonResponse
    {
        if ($request->hasHeader('X-Twilio-Signature')) {
            if (! $this->hasValidTwilioSignature($request)) {
                return response()->json(['message' => 'Assinatura inválida.'], 401);
            }

            $this->handleTwilioMessage($request);

            return response()->json(['success' => true]);
        }

        if (! $this->hasValidSignature($request)) {
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        $payload = $request->all();
        if (($payload['object'] ?? null) !== 'whatsapp_business_account' || ! is_array($payload['entry'] ?? null)) {
            return response()->json(['message' => 'Payload inválido.'], 422);
        }

        $entries = $payload['entry'];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];
                $metadata = $value['metadata'] ?? [];

                foreach ($messages as $message) {
                    $this->handleMessage($message, $metadata);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    private function handleTwilioMessage(Request $request): void
    {
        $body = $request->string('Body')->toString();
        $from = $request->string('From')->toString();

        if ($body === '' || $from === '' || ! $this->looksLikeConfirmation($body)) {
            return;
        }

        $phone = $this->normalizePhone($from);
        $appointment = Appointment::query()
            ->with('psychologist:id,name,whatsapp_sender_phone_id')
            ->whereHas('patient', function ($query) use ($phone) {
                $query->where('phone', 'like', '%'.substr($phone, -8));
            })
            ->where('status', 'scheduled')
            ->orderByDesc('start_at')
            ->first();

        if (! $appointment) {
            Log::info('Twilio WhatsApp confirmation received but appointment not found');

            return;
        }

        $appointment->status = 'done';
        $appointment->confirmation_channel = 'whatsapp_reply';
        $appointment->confirmation_sent_at = now();
        $appointment->save();

        Log::info('Appointment confirmed via Twilio WhatsApp reply', [
            'appointment_id' => $appointment->id,
            'psychologist_id' => $appointment->psychologist_id,
        ]);
    }

    private function handleMessage(array $message, array $metadata = []): void
    {
        $body = $message['text']['body'] ?? null;
        $from = $message['from'] ?? null;

        if (! $body || ! $from) {
            return;
        }

        if (! $this->looksLikeConfirmation($body)) {
            return;
        }

        $phone = $this->normalizePhone($from);
        $senderPhoneId = $metadata['phone_number_id'] ?? null;
        if (! $senderPhoneId) {
            return;
        }

        $appointmentQuery = Appointment::query()
            ->with('psychologist:id,name,whatsapp_sender_phone_id')
            ->whereHas('patient', function ($query) use ($phone) {
                $query->where('phone', 'like', '%'.substr($phone, -8));
            })
            ->where('status', 'scheduled');

        $this->scopeBySenderPhoneId($appointmentQuery, $senderPhoneId);

        $appointment = $appointmentQuery
            ->orderByDesc('start_at')
            ->first();

        if (! $appointment) {
            Log::info('WhatsApp confirmation message received but appointment not found', [
                'sender_phone_id' => $senderPhoneId,
            ]);

            return;
        }

        $appointment->status = 'done';
        $appointment->confirmation_channel = 'whatsapp_reply';
        $appointment->confirmation_sent_at = now();
        $appointment->save();

        Log::info('Appointment confirmed via WhatsApp reply', [
            'appointment_id' => $appointment->id,
            'psychologist_id' => $appointment->psychologist_id,
            'sender_phone_id' => $senderPhoneId,
        ]);
    }

    private function scopeBySenderPhoneId($query, ?string $senderPhoneId): void
    {
        $senderPhoneId = trim((string) $senderPhoneId);

        if ($senderPhoneId === '') {
            return;
        }

        $defaultPhoneId = trim((string) config('services.whatsapp.phone_id'));

        $query->whereHas('psychologist', function ($query) use ($senderPhoneId, $defaultPhoneId) {
            $query->where('whatsapp_sender_phone_id', $senderPhoneId);

            if ($defaultPhoneId !== '' && hash_equals($defaultPhoneId, $senderPhoneId)) {
                $query->orWhereNull('whatsapp_sender_phone_id')
                    ->orWhere('whatsapp_sender_phone_id', '');
            }
        });
    }

    private function hasValidSignature(Request $request): bool
    {
        $appSecret = (string) config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        if ($appSecret === '' || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, substr($signature, 7));
    }

    private function hasValidTwilioSignature(Request $request): bool
    {
        $authToken = (string) config('services.twilio.auth_token');
        $signature = (string) $request->header('X-Twilio-Signature');

        if ($authToken === '' || $signature === '') {
            return false;
        }

        $parameters = $request->post();
        ksort($parameters);

        $data = $request->fullUrl();
        foreach ($parameters as $key => $value) {
            $data .= $key.(is_array($value) ? implode('', $value) : $value);
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expected, $signature);
    }

    private function looksLikeConfirmation(string $body): bool
    {
        $normalized = mb_strtolower(trim($body));

        $keywords = ['confirmado', 'confirmo', 'sim', 'ok'];

        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        if (str_starts_with($digits, '55') === false && strlen($digits) >= 10) {
            return '55'.$digits;
        }

        return $digits;
    }
}
