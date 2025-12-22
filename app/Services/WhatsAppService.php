<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function __construct(
        private ?string $token = null,
        private ?string $phoneId = null,
        private ?string $businessName = null,
    ) {
        $this->token = $this->token ?? config('services.whatsapp.token');
        $this->phoneId = $this->phoneId ?? config('services.whatsapp.phone_id');
        $this->businessName = $this->businessName ?? config('services.whatsapp.business_name', config('app.name', 'Clínica'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->phoneId);
    }

    public function sendSessionConfirmation(Appointment $appointment): bool
    {
        if (!$this->isConfigured()) {
            Log::info('WhatsApp confirmation skipped: service not configured', [
                'appointment_id' => $appointment->id,
            ]);
            return false;
        }

        $patient = $appointment->patient;

        if (!$patient?->phone) {
            Log::info('WhatsApp confirmation skipped: patient without phone', [
                'appointment_id' => $appointment->id,
            ]);
            return false;
        }

        $phone = $this->formatPhoneNumber($patient->phone);

        if (!$phone) {
            Log::info('WhatsApp confirmation skipped: invalid phone format', [
                'appointment_id' => $appointment->id,
                'raw_phone' => $patient->phone,
            ]);
            return false;
        }

        $message = $this->buildConfirmationMessage($appointment);

        try {
            $this->sendText($phone, $message);

            return true;
        } catch (\Throwable $exception) {
            Log::error('WhatsApp confirmation failed', [
                'appointment_id' => $appointment->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendText(string $phone, string $message): void
    {
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->post(
                sprintf('https://graph.facebook.com/v17.0/%s/messages', $this->phoneId),
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
