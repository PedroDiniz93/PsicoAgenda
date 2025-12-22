<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PsychologistProfileUpdateRequest;
use App\Http\Requests\PsychologistSettingsUpdateRequest;
use App\Models\Psychologist;
use Illuminate\Http\Request;

class PsychologistController extends Controller
{
    private function resolvePsychologist(Request $request): Psychologist
    {
        $user = $request->user()->loadMissing('psychologist');

        $psychologist = $user->psychologist;

        abort_if(!$psychologist, 404, 'Perfil de psicólogo não encontrado.');

        return $psychologist;
    }

    public function show(Request $request)
    {
        $psychologist = $this->resolvePsychologist($request);

        return response()->json([
            'psychologist' => $psychologist,
            'user' => $request->user(),
        ]);
    }

    public function update(PsychologistProfileUpdateRequest $request)
    {
        $psychologist = $this->resolvePsychologist($request);

        $psychologist->fill($request->validated());
        $psychologist->save();

        return response()->json([
            'psychologist' => $psychologist->fresh(),
        ]);
    }

    public function updateSettings(PsychologistSettingsUpdateRequest $request)
    {
        $psychologist = $this->resolvePsychologist($request);

        $data = $request->validated();

        if (array_key_exists('google_calendar_token', $data)) {
            $psychologist->google_calendar_token = $data['google_calendar_token'];
        }

        if (array_key_exists('whatsapp_confirm_enabled', $data)) {
            $psychologist->whatsapp_confirm_enabled = (bool) $data['whatsapp_confirm_enabled'];
        }

        if (array_key_exists('whatsapp_confirm_days_before', $data)) {
            $psychologist->whatsapp_confirm_days_before = (int) $data['whatsapp_confirm_days_before'];
        }

        if (array_key_exists('email_confirm_enabled', $data)) {
            $psychologist->email_confirm_enabled = (bool) $data['email_confirm_enabled'];
        }

        if (array_key_exists('sms_confirm_enabled', $data)) {
            $psychologist->sms_confirm_enabled = (bool) $data['sms_confirm_enabled'];
        }

        $psychologist->save();

        return response()->json([
            'psychologist' => $psychologist->fresh(),
        ]);
    }
}
