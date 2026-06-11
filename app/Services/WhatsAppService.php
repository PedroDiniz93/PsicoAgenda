<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Psychologist;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function __construct(
        private ?string $token = null,
        private ?string $defaultPhoneId = null,
        private ?string $businessName = null,
    ) {
        $this->token = $this->token ?? config('services.whatsapp.token');
        $this->defaultPhoneId = $this->defaultPhoneId ?? config('services.whatsapp.phone_id');
        $this->businessName = $this->businessName ?? config('services.whatsapp.business_name', config('app.name', 'Clínica'));
    }

    public function isConfigured(?Psychologist $psychologist = null): bool
    {
        return !empty($this->token) && !empty($this->senderPhoneId($psychologist));
    }

    public function sendSessionConfirmation(Appointment $appointment): bool
    {
        $appointment->loadMissing(['patient', 'psychologist']);
        $psychologist = $appointment->psychologist;
        $senderPhoneId = $this->senderPhoneId($psychologist);

        if (!$this->isConfigured($psychologist)) {
            Log::info('WhatsApp confirmation skipped: service not configured', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
                'has_token' => !empty($this->token),
                'has_sender_phone_id' => !empty($senderPhoneId),
            ]);
            return false;
        }

        $patient = $appointment->patient;

        if (!$patient?->phone) {
            Log::info('WhatsApp confirmation skipped: patient without phone', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
            ]);
            return false;
        }

        $phone = $this->formatPhoneNumber($patient->phone);

        if (!$phone) {
            Log::info('WhatsApp confirmation skipped: invalid phone format', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
                'raw_phone' => $patient->phone,
            ]);
            return false;
        }

        $message = $this->buildConfirmationMessage($appointment);

        try {
            $this->sendText($phone, $message, $senderPhoneId);

            Log::info('WhatsApp confirmation sent', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
                'sender_phone_id' => $senderPhoneId,
                'sender_display_number' => $psychologist?->whatsapp_sender_display_number,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('WhatsApp confirmation failed', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
                'sender_phone_id' => $senderPhoneId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendText(string $phone, string $message, string $senderPhoneId): void
    {
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->post(
                sprintf('https://graph.facebook.com/v17.0/%s/messages', $senderPhoneId),
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]
            );

        if ($response->failed()) {
            throw new \RuntimeException((string) $response->body());
        }
    }

    private function buildConfirmationMessage(Appointment $appointment): string
    {
        $patientName = $appointment->patient?->name ?? 'Paciente';
        $psychologistName = $appointment->psychologist?->name ?? $this->businessName;
        $timezone = $appointment->psychologist?->timezone ?? config('app.timezone');

        $startAt = $appointment->start_at instanceof Carbon
            ? $appointment->start_at->copy()
            : Carbon::parse($appointment->start_at);

        $startAt->setTimezone($timezone);

        $date = $startAt->translatedFormat('d/m/Y');
        $time = $startAt->format('H:i');

        return sprintf(
            "Olá %s! Aqui é %s. Sua sessão está confirmada para %s às %s. Caso precise reagendar ou cancelar, responda esta mensagem.",
            $patientName,
            $psychologistName,
            $date,
            $time
        );
    }

    private function senderPhoneId(?Psychologist $psychologist): ?string
    {
        $phoneId = trim((string) ($psychologist?->whatsapp_sender_phone_id ?: $this->defaultPhoneId));

        return $phoneId === '' ? null : $phoneId;
    }

    private function formatPhoneNumber(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $hasPlus = str_contains($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw);

        if (!$digits) {
            return null;
        }

        $digits = ltrim($digits, '0');

        if ($hasPlus && strlen($digits) >= 8) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return '+' . $digits;
        }

        if (strlen($digits) >= 10) {
            return '+55' . $digits;
        }

        return null;
    }
}
