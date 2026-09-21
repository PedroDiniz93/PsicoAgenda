<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Psychologist;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function isConfigured(?Psychologist $psychologist = null): bool
    {
        $twilio = config('services.twilio');

        return ! empty($twilio['account_sid'])
            && ! empty($twilio['api_key'])
            && ! empty($twilio['api_secret'])
            && ! empty($twilio['whatsapp_from'])
            && ! empty($twilio['whatsapp_content_sid']);
    }

    public function sendSessionConfirmation(Appointment $appointment): bool
    {
        $appointment->loadMissing(['patient', 'psychologist']);
        $psychologist = $appointment->psychologist;

        if (! $this->isConfigured($psychologist)) {
            Log::info('WhatsApp confirmation skipped: Twilio service not configured', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
                'provider' => 'twilio',
            ]);

            return false;
        }

        $patient = $appointment->patient;

        if (! $patient?->phone) {
            Log::info('WhatsApp confirmation skipped: patient without phone', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
            ]);

            return false;
        }

        $phone = $this->formatPhoneNumber($patient->phone);

        if (! $phone) {
            Log::info('WhatsApp confirmation skipped: invalid phone format', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
            ]);

            return false;
        }

        try {
            $this->sendTemplate($phone, $appointment);

            Log::info('WhatsApp confirmation sent', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
                'provider' => 'twilio',
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('WhatsApp confirmation failed', [
                'appointment_id' => $appointment->id,
                'psychologist_id' => $psychologist?->id,
                'provider' => 'twilio',
                'error_class' => $exception::class,
            ]);

            return false;
        }
    }

    private function sendTemplate(string $phone, Appointment $appointment): void
    {
        $twilio = config('services.twilio');
        $timezone = $appointment->psychologist->timezone ?? config('app.timezone');
        $startAt = $appointment->start_at->copy()->setTimezone($timezone);
        $contentVariables = $twilio['whatsapp_sandbox']
            ? [
                '1' => $startAt->format('d/m/Y'),
                '2' => $startAt->format('H:i'),
            ]
            : [
                '1' => $appointment->patient->name,
                '2' => $appointment->psychologist->name,
                '3' => $startAt->format('d/m/Y'),
                '4' => $startAt->format('H:i'),
            ];

        $response = Http::asForm()
            ->withBasicAuth($twilio['api_key'], $twilio['api_secret'])
            ->acceptJson()
            ->post('https://api.twilio.com/2010-04-01/Accounts/'.$twilio['account_sid'].'/Messages.json', [
                'From' => $this->normalizeTwilioAddress($twilio['whatsapp_from']),
                'To' => 'whatsapp:'.$phone,
                'ContentSid' => $twilio['whatsapp_content_sid'],
                'ContentVariables' => json_encode($contentVariables, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Twilio WhatsApp message request failed with status '.$response->status());
        }
    }

    private function normalizeTwilioAddress(string $value): string
    {
        $value = trim($value);

        return str_starts_with($value, 'whatsapp:') ? $value : 'whatsapp:'.$value;
    }

    private function formatPhoneNumber(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $hasPlus = str_contains($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw);

        if (! $digits) {
            return null;
        }

        $digits = ltrim($digits, '0');

        if ($hasPlus && strlen($digits) >= 8) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return '+'.$digits;
        }

        if (strlen($digits) >= 10) {
            return '+55'.$digits;
        }

        return null;
    }
}
