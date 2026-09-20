<?php

namespace App\Http\Controllers\Api;

use App\Events\OnlineSessionSignal;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\OnlineSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        return $this->broadcastSignal($request, $session);
    }

    public function publicSignal(Request $request, string $token)
    {
        $session = OnlineSession::where('token_hash', OnlineSession::hashToken($token))
            ->firstOrFail()
            ->refreshStatus();

        abort_if(! in_array($session->status, ['waiting', 'active'], true), 410, 'Esta sala não está disponível.');

        return $this->broadcastSignal($request, $session);
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
            'channel' => 'online-session.'.$session->token_hash,
        ];
    }

    private function broadcastSignal(Request $request, OnlineSession $session)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:presence,offer,answer,ice-candidate,chat'],
            'payload' => ['required', 'array'],
        ]);

        $data = $validator->validate();
        abort_if(strlen((string) json_encode($data['payload'])) > 16_000, 422, 'A mensagem da sala é muito grande.');

        if ($session->status === 'waiting') {
            $session->update([
                'status' => 'active',
                'started_at' => now(),
            ]);
        }

        broadcast(new OnlineSessionSignal($session, $data['type'], $data['payload']))->toOthers();

        return response()->json(['status' => 'sent']);
    }
}
