<?php

namespace App\Http\Controllers\Api;

use App\Events\OnlineSessionSignal;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\OnlineSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OnlineSessionController extends Controller
{
    public function store(Request $request, int $appointmentId)
    {
        $psychologist = $request->user()->loadMissing('psychologist')->psychologist;

        abort_if(! $psychologist, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        $appointment = Appointment::where('psychologist_id', $psychologist->id)
            ->with('patient')
            ->findOrFail($appointmentId);

        abort_if($appointment->type !== 'online', 422, 'Este compromisso não está configurado como atendimento online.');
        abort_if($appointment->status === 'canceled', 422, 'Não é possível iniciar uma sala para um compromisso cancelado.');

        $existing = OnlineSession::where('appointment_id', $appointment->id)
            ->whereIn('status', ['waiting', 'active'])
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update(['status' => 'ended', 'ended_at' => now()]);
        }

        [$session, $token] = OnlineSession::createWithToken([
            'psychologist_id' => $psychologist->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'status' => 'waiting',
        ], Carbon::now()->addHours(12));

        return response()->json([
            ...$this->psychologistPayload($session),
            'patient_token' => $token,
            'channel' => 'online-session.'.$session->token_hash,
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $psychologist = $request->user()->loadMissing('psychologist')->psychologist;

        abort_if(! $psychologist, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        $session = OnlineSession::where('psychologist_id', $psychologist->id)
            ->findOrFail($id)
            ->refreshStatus();

        return response()->json($this->psychologistPayload($session));
    }

    public function end(Request $request, int $id)
    {
        $psychologist = $request->user()->loadMissing('psychologist')->psychologist;

        abort_if(! $psychologist, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        $session = OnlineSession::where('psychologist_id', $psychologist->id)
            ->findOrFail($id);

        if ($session->status !== 'ended') {
            $session->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);
        }

        return response()->json($this->psychologistPayload($session->refresh()));
    }

    public function iceServers(Request $request, int $id)
    {
        $psychologist = $request->user()->loadMissing('psychologist')->psychologist;

        abort_if(! $psychologist, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        $session = OnlineSession::where('psychologist_id', $psychologist->id)
            ->findOrFail($id)
            ->refreshStatus();

        abort_if(! in_array($session->status, ['waiting', 'active'], true), 410, 'Esta sala não está disponível.');

        return $this->twilioIceServers();
    }

    public function publicIceServers(string $token)
    {
        $session = OnlineSession::where('token_hash', OnlineSession::hashToken($token))
            ->firstOrFail()
            ->refreshStatus();

        abort_if(! in_array($session->status, ['waiting', 'active'], true), 410, 'Esta sala não está disponível.');

        return $this->twilioIceServers();
    }

    public function publicShow(string $token)
    {
        $session = OnlineSession::where('token_hash', OnlineSession::hashToken($token))
            ->firstOrFail()
            ->refreshStatus();

        abort_if(
            ! in_array($session->status, ['waiting', 'active'], true),
            410,
            'Esta sala não está disponível.'
        );

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'expires_at' => $session->expires_at,
            'channel' => 'online-session.'.$session->token_hash,
        ]);
    }

    public function signal(Request $request, int $id)
    {
        $psychologist = $request->user()->loadMissing('psychologist')->psychologist;

        abort_if(! $psychologist, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        $session = OnlineSession::where('psychologist_id', $psychologist->id)
            ->findOrFail($id)
            ->refreshStatus();

        abort_if(! in_array($session->status, ['waiting', 'active'], true), 410, 'Esta sala não está disponível.');

        return $this->broadcastSignal($request, $session, 'psychologist');
    }

    public function publicSignal(Request $request, string $token)
    {
        $session = OnlineSession::where('token_hash', OnlineSession::hashToken($token))
            ->firstOrFail()
            ->refreshStatus();

        abort_if(! in_array($session->status, ['waiting', 'active'], true), 410, 'Esta sala não está disponível.');

        return $this->broadcastSignal($request, $session, 'patient');
    }

    private function psychologistPayload(OnlineSession $session): array
    {
        return [
            'id' => $session->id,
            'appointment_id' => $session->appointment_id,
            'patient_id' => $session->patient_id,
            'status' => $session->status,
            'expires_at' => $session->expires_at,
            'started_at' => $session->started_at,
            'ended_at' => $session->ended_at,
            'patient_waiting_for_approval' => $session->status === 'waiting'
                && $session->patient_connection_id
                && $session->patient_connection_at?->greaterThan(now()->subSeconds(45)),
            'channel' => 'online-session.'.$session->token_hash,
        ];
    }

    private function twilioIceServers()
    {
        $twilio = config('services.twilio');

        if (! $twilio['account_sid'] || ! $twilio['api_key'] || ! $twilio['api_secret']) {
            return response()->json(['ice_servers' => []]);
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($twilio['api_key'], $twilio['api_secret'])
                ->timeout(8)
                ->post('https://api.twilio.com/2010-04-01/Accounts/'.$twilio['account_sid'].'/Tokens.json', [
                    'Ttl' => max(300, min((int) $twilio['turn_ttl'], 86400)),
                ]);

            if ($response->failed()) {
                Log::warning('online-session TURN token request failed', [
                    'status' => $response->status(),
                ]);

                return response()->json(['message' => 'Não foi possível preparar a conexão de rede da sala.'], 502);
            }

            return response()->json([
                'ice_servers' => $response->json('ice_servers', []),
            ]);
        } catch (\Throwable $cause) {
            Log::warning('online-session TURN token request exception', [
                'exception' => $cause::class,
            ]);

            return response()->json(['message' => 'Não foi possível preparar a conexão de rede da sala.'], 502);
        }
    }

    private function broadcastSignal(Request $request, OnlineSession $session, string $expectedRole)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:presence,entry-approved,offer,answer,ice-candidate,chat,media-state'],
            'payload' => ['required', 'array'],
        ]);

        $data = $validator->validate();
        abort_if(strlen((string) json_encode($data['payload'])) > 16_000, 422, 'A mensagem da sala é muito grande.');

        if (in_array($data['type'], ['presence', 'entry-approved'], true)) {
            abort_unless(($data['payload']['role'] ?? null) === $expectedRole, 403, 'Papel inválido para este canal.');
        }

        if ($data['type'] === 'entry-approved') {
            abort_unless($expectedRole === 'psychologist', 403, 'Somente o psicólogo pode autorizar a entrada.');
        }

        if ($expectedRole === 'patient' && $data['type'] === 'presence') {
            $connectionId = $data['payload']['connection_id'] ?? null;
            abort_unless(is_string($connectionId) && preg_match('/^[A-Za-z0-9-]{16,100}$/', $connectionId), 422, 'Identificador de conexão inválido.');

            $session = DB::transaction(function () use ($session, $connectionId, $data) {
                $lockedSession = OnlineSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
                $lastConnectionAt = $lockedSession->patient_connection_at;
                $connectionIsRecent = $lastConnectionAt && $lastConnectionAt->greaterThan(now()->subSeconds(45));
                $sameConnection = $lockedSession->patient_connection_id === $connectionId;
                $state = $data['payload']['state'] ?? null;

                if ($state === 'left') {
                    if ($sameConnection) {
                        $lockedSession->update([
                            'patient_connection_id' => null,
                            'patient_connection_at' => null,
                        ]);
                    }

                    return $lockedSession->refresh();
                }

                abort_if($connectionIsRecent && ! $sameConnection, 409, 'Esta sala já está sendo usada por outro paciente.');

                $lockedSession->update([
                    'patient_connection_id' => $connectionId,
                    'patient_connection_at' => now(),
                    ...($state === 'joined' && $lockedSession->status === 'waiting'
                        ? ['status' => 'active', 'started_at' => now()]
                        : []),
                ]);

                return $lockedSession->refresh();
            });
        }

        if ($expectedRole === 'patient' && $data['type'] !== 'presence') {
            $connectionId = $data['payload']['connection_id'] ?? null;
            abort_unless(is_string($connectionId) && preg_match('/^[A-Za-z0-9-]{16,100}$/', $connectionId), 422, 'Identificador de conexão inválido.');

            $lockedSession = OnlineSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $connectionIsRecent = $lockedSession->patient_connection_at
                && $lockedSession->patient_connection_at->greaterThan(now()->subSeconds(45));
            abort_unless($lockedSession->patient_connection_id === $connectionId && $connectionIsRecent, 409, 'Esta conexão não está autorizada para a sala.');

            $lockedSession->update(['patient_connection_at' => now()]);
            $session = $lockedSession->refresh();
        }

        if ($session->status === 'waiting'
            && $data['type'] === 'presence'
            && ($data['payload']['state'] ?? null) === 'joined'
            && $expectedRole === 'patient'
            && ! $session->started_at) {
            $session->update([
                'status' => 'active',
                'started_at' => now(),
            ]);
        }

        Log::info('online-session signal dispatch', [
            'session_id' => $session->id,
            'type' => $data['type'],
            'role' => $data['type'] === 'presence' ? ($data['payload']['role'] ?? null) : null,
            'status' => $session->status,
            'socket_id_present' => $request->headers->has('X-Socket-ID'),
        ]);

        broadcast(new OnlineSessionSignal($session, $data['type'], $data['payload']))->toOthers();

        return response()->json(['status' => 'sent']);
    }
}
