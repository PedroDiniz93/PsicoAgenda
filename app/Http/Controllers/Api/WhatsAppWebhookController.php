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
        $payload = $request->all();
        $entries = $payload['entry'] ?? [];

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

    private function handleMessage(array $message, array $metadata = []): void
    {
        $body = $message['text']['body'] ?? null;
        $from = $message['from'] ?? null;

        if (!$body || !$from) {
            return;
        }

        if (!$this->looksLikeConfirmation($body)) {
            return;
        }

        $phone = $this->normalizePhone($from);
        $senderPhoneId = $metadata['phone_number_id'] ?? null;

        $appointmentQuery = Appointment::query()
            ->with('psychologist:id,name,whatsapp_sender_phone_id')
            ->whereHas('patient', function ($query) use ($phone) {
                $query->where('phone', 'like', '%' . substr($phone, -8));
            })
            ->where('status', 'scheduled');

        $this->scopeBySenderPhoneId($appointmentQuery, $senderPhoneId);

        $appointment = $appointmentQuery
            ->orderByDesc('start_at')
            ->first();

        if (!$appointment) {
            Log::info('WhatsApp confirmation message received but appointment not found', [
                'from' => $from,
                'sender_phone_id' => $senderPhoneId,
                'message' => $body,
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
            return '55' . $digits;
        }

        return $digits;
    }
}
