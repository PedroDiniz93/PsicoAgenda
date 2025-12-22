<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(
        private ?string $endpoint = null,
        private ?string $token = null,
        private ?string $from = null,
    ) {
        $config = config('services.sms');

        $this->endpoint = $this->endpoint ?? $config['endpoint'] ?? null;
        $this->token = $this->token ?? $config['token'] ?? null;
        $this->from = $this->from ?? $config['from'] ?? null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->endpoint) && !empty($this->token) && !empty($this->from);
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::info('SMS service not configured, skipping send.', ['phone' => $phone]);

            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->post($this->endpoint, [
                    'from' => $this->from,
                    'to' => $phone,
                    'message' => $message,
                ]);

            if ($response->failed()) {
                throw new \RuntimeException((string) $response->body());
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('SMS send failed', [
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
