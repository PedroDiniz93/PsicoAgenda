<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Psychologist;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    private const BASE_URL = 'https://www.googleapis.com/calendar/v3';

    public function syncAppointment(Appointment $appointment): void
    {
        $appointment->loadMissing(['patient', 'psychologist']);

        $psychologist = $appointment->psychologist;
        if (!$psychologist) {
            return;
        }

        $token = $this->resolveToken($psychologist);
        if (!$token) {
            return;
        }

        $payload = $this->buildEventPayload($appointment, $psychologist, $appointment->patient);
        $conferenceEnabled = $this->shouldCreateConference($appointment);

        if ($appointment->google_event_id) {
            $response = $this->request(
                $token,
                'patch',
                $this->eventUrl($appointment->google_event_id, $conferenceEnabled),
                $payload
            );

            if ($response && $response->successful()) {
                $this->storeEventMetadata($appointment, $response->json());

                return;
            }

            // If the event was removed manually, create a new one.
        }

        $response = $this->request(
            $token,
            'post',
            $this->eventsUrl($conferenceEnabled),
            $payload
        );

        if (!$response || !$response->successful()) {
            return;
        }

        $eventId = $response->json('id');

        $this->storeEventMetadata($appointment, $response->json() ?? [], $eventId);
    }

    public function deleteAppointment(Appointment $appointment): void
    {
        $appointment->loadMissing('psychologist');
        $psychologist = $appointment->psychologist;

        if (!$psychologist || !$appointment->google_event_id) {
            return;
        }

        $token = $this->resolveToken($psychologist);
        if (!$token) {
            return;
        }

        $response = $this->request(
            $token,
            'delete',
            $this->eventUrl($appointment->google_event_id)
        );

        if ($response && ($response->successful() || $response->status() === 404)) {
            $appointment->forceFill([
                'google_event_id' => null,
                'meeting_url' => null,
                'meeting_provider' => null,
            ])->save();
        }
    }

    private function buildEventPayload(Appointment $appointment, Psychologist $psychologist, ?Patient $patient): array
    {
        $timezone = $psychologist->timezone ?? config('app.timezone', 'UTC');

        $start = $appointment->start_at?->copy()->tz($timezone);
        $end = $appointment->end_at?->copy()->tz($timezone);

        $summary = trim(sprintf(
            'Sessão com %s',
            $patient?->name ?: 'paciente'
        ));

        $status = 'Status: Agendado';
        if ($appointment->status == 'done') {
            $status = 'Status: Finalizado';
        }

        $descriptionParts = array_filter([
            $patient?->email ? 'E-mail do paciente: ' . $patient->email : null,
            $patient?->phone ? 'Telefone: ' . $patient->phone : null,
            $appointment->type ? 'Tipo: ' . ($appointment->type === 'online' ? 'Online' : 'Presencial') : null,
            $status
        ]);

        $payload = [
            'summary' => $summary,
            'description' => implode(PHP_EOL, $descriptionParts),
            'start' => [
                'dateTime' => $start?->toIso8601String(),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $end?->toIso8601String(),
                'timeZone' => $timezone,
            ],
            'reminders' => [
                'useDefault' => true,
            ],
        ];

        if ($this->shouldCreateConference($appointment)) {
            $payload['conferenceData'] = [
                'createRequest' => [
                    'requestId' => (string) Str::uuid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ];
        }

        return $payload;
    }

    private function resolveToken(Psychologist $psychologist): ?array
    {
        $encrypted = $psychologist->google_calendar_token;
        if (!$encrypted) {
            return null;
        }

        try {
            $token = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('Falha ao descriptografar token do Google Calendar.', [
                'psychologist_id' => $psychologist->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (!is_array($token) || empty($token['access_token'])) {
            return null;
        }

        return $this->refreshTokenIfNeeded($psychologist, $token);
    }

    private function refreshTokenIfNeeded(Psychologist $psychologist, array $token): ?array
    {
        $expiresAt = $token['expires_at'] ?? null;
        $expiresSoon = false;

        if ($expiresAt) {
            try {
                $expiry = Carbon::parse($expiresAt);
                $expiresSoon = $expiry->lessThanOrEqualTo(now()->addMinutes(2));
            } catch (\Throwable) {
                $expiresSoon = true;
            }
        }

        if (!$expiresSoon) {
            return $token;
        }

        $refreshToken = $token['refresh_token'] ?? null;

        if (!$refreshToken) {
            Log::warning('Token do Google Calendar expirado e sem refresh token.', [
                'psychologist_id' => $psychologist->id,
            ]);

            return null;
        }

        $config = config('services.google');
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');

        if (!$clientId || !$clientSecret) {
            return null;
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao atualizar token do Google Calendar.', [
                'psychologist_id' => $psychologist->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful() || !isset($response['access_token'])) {
            Log::warning('Resposta inválida ao atualizar token do Google Calendar.', [
                'psychologist_id' => $psychologist->id,
                'status' => $response?->status(),
                'body' => $response?->body(),
            ]);

            return null;
        }

        $token['access_token'] = $response['access_token'];
        $token['expires_at'] = now()->addSeconds((int) ($response['expires_in'] ?? 0))->toIso8601String();

        if (!empty($response['refresh_token'])) {
            $token['refresh_token'] = $response['refresh_token'];
        }

        $psychologist->forceFill([
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
        ])->save();

        return $token;
    }

    private function request(array $token, string $method, string $url, array $payload = [])
    {
        try {
            $client = Http::withToken($token['access_token'])
                ->acceptJson();

            return match (strtolower($method)) {
                'patch' => $client->patch($url, $payload),
                'post' => $client->post($url, $payload),
                'delete' => $client->delete($url),
                default => $client->get($url),
            };
        } catch (\Throwable $e) {
            Log::warning('Erro ao conversar com o Google Calendar.', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function eventsUrl(bool $withConference = false): string
    {
        $url = self::BASE_URL . '/calendars/primary/events';

        return $withConference ? $url . '?conferenceDataVersion=1' : $url;
    }

    private function eventUrl(string $eventId, bool $withConference = false): string
    {
        $url = self::BASE_URL . '/calendars/primary/events/' . urlencode($eventId);

        return $withConference ? $url . '?conferenceDataVersion=1' : $url;
    }

    private function shouldCreateConference(Appointment $appointment): bool
    {
        return $appointment->type === 'online';
    }

    private function storeEventMetadata(Appointment $appointment, array $event, ?string $eventId = null): void
    {
        $meetingLink = $this->extractMeetingLink($event);

        $attributes = [
            'meeting_url' => $this->shouldCreateConference($appointment) ? $meetingLink : null,
            'meeting_provider' => $meetingLink ? 'google_meet' : null,
        ];

        if ($eventId) {
            $attributes['google_event_id'] = $eventId;
        }

        $appointment->forceFill($attributes)->save();
    }

    private function extractMeetingLink(array $event): ?string
    {
        $link = Arr::get($event, 'hangoutLink');

        if ($link) {
            return $link;
        }

        $entryPoints = Arr::get($event, 'conferenceData.entryPoints', []);

        foreach ($entryPoints as $entry) {
            if (($entry['entryPointType'] ?? null) === 'video' && !empty($entry['uri'])) {
                return $entry['uri'];
            }
        }

        return null;
    }
}
