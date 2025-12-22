<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecurringAppointment;
use App\Services\RecurringAppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RecurringAppointmentController extends Controller
{
    public function __construct(
        private readonly RecurringAppointmentService $recurringAppointmentService
    ) {
    }

    public function destroy(Request $request, int $id)
    {
        $psychologistId = $this->psychologistId($request);

        $recurrence = RecurringAppointment::where('psychologist_id', $psychologistId)
            ->findOrFail($id);

        if ($recurrence->status !== 'ended') {
            $recurrence->status = 'ended';
            if (!$recurrence->end_date) {
                $timezone = $recurrence->timezone ?? config('app.timezone');
                $recurrence->end_date = Carbon::now($timezone)->toDateString();
            }
            $recurrence->save();
        }

        $canceled = $this->recurringAppointmentService->cancelFutureOccurrences($recurrence);

        return response()->json([
            'status' => $recurrence->status,
            'canceled' => $canceled,
        ]);
    }

    private function psychologistId(Request $request): int
    {
        $user = $request->user()->loadMissing('psychologist');
        $psychologistId = $user->psychologist?->id;

        abort_if(
            !$psychologistId,
            403,
            'Usuário autenticado não possui um perfil de psicólogo.'
        );

        return (int) $psychologistId;
    }
}
