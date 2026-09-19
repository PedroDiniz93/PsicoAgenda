<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GoogleCalendarController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendarService
    ) {
    }

    public function events(Request $request)
    {
        $psychologist = $request->user()->loadMissing('psychologist')->psychologist;
        abort_if(!$psychologist, 403, 'Perfil de psicólogo não encontrado.');

        [$from, $to] = $this->parseRange($request);

        return response()->json([
            'events' => $this->googleCalendarService->listExternalEvents($psychologist, $from, $to),
        ]);
    }

    private function parseRange(Request $request): array
    {
        $fromValue = $request->query('from');
        $toValue = $request->query('to');

        abort_if(!$fromValue || !$toValue, 422, 'Informe o intervalo da agenda.');

        try {
            $from = Carbon::parse($fromValue)->startOfDay();
            $to = Carbon::parse($toValue)->endOfDay();
        } catch (\Throwable) {
            abort(422, 'O intervalo da agenda é inválido.');
        }

        abort_if($from->gte($to), 422, 'O início deve ser anterior ao fim.');
        abort_if($from->diffInDays($to) > 31, 422, 'O intervalo máximo é de 31 dias.');

        return [$from, $to];
    }
}
