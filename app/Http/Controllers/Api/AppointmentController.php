<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentStoreRequest;
use App\Http\Requests\AppointmentUpdateRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Psychologist;
use App\Models\RecurringAppointment;
use App\Services\GoogleCalendarService;
use App\Services\RecurringAppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendarService,
        private readonly RecurringAppointmentService $recurringAppointmentService
    ) {
    }

    private function psychologist(Request $request): Psychologist
    {
        $user = $request->user()->loadMissing('psychologist');

        $psychologist = $user->psychologist;

        abort_if(
            !$psychologist,
            403,
            'Usuário autenticado não possui um perfil de psicólogo.'
        );

        return $psychologist;
    }

    private function psychologistId(Request $request): int
    {
        return (int) $this->psychologist($request)->id;
    }

    private function ensureValidRange(Carbon $start, Carbon $end): void
    {
        abort_if($start->gte($end), 422, 'Horário inicial deve ser antes do final.');
    }

    private function ensureNoOverlap(int $psychologistId, Carbon $start, Carbon $end, ?int $ignoreId = null): void
    {
        $query = Appointment::where('psychologist_id', $psychologistId)
            ->where('status', '!=', 'canceled')
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        abort_if(
            $query->exists(),
            422,
            'Já existe um agendamento nesse intervalo de horário.'
        );
    }

    public function index(Request $request)
    {
        $psychologistId = $this->psychologistId($request);

        $query = Appointment::where('psychologist_id', $psychologistId)
            ->with(['patient', 'recurrence'])
            ->orderBy('start_at');

        if ($request->filled('from')) {
            $from = $this->parseDate($request->string('from')->toString(), 'from')->startOfDay();
            $query->where('start_at', '>=', $from);
        }

        if ($request->filled('to')) {
            $to = $this->parseDate($request->string('to')->toString(), 'to')->endOfDay();
            $query->where('start_at', '<=', $to);
        }

        return response()->json($query->get());
    }

    public function store(AppointmentStoreRequest $request)
    {
        $psychologist = $this->psychologist($request);
        $psychologistId = $psychologist->id;
        $data = $request->validated();

        $patient = $this->findOwnedPatient($data['patient_id'], $psychologistId);
        $repeatWeekly = (bool) ($data['repeat_weekly'] ?? false);
        $repeatUntil = $data['repeat_until'] ?? null;
        unset($data['repeat_weekly'], $data['repeat_until']);

        $start = Carbon::parse($data['start_at']);
        $end = Carbon::parse($data['end_at']);
        $this->ensureValidRange($start, $end);
        $this->ensureNoOverlap($psychologistId, $start, $end);
        $data['start_at'] = $start;
        $data['end_at'] = $end;
        $this->applyPatientFee($data, $patient);

        $appointment = Appointment::create([
            'psychologist_id' => $psychologistId,
            ...$data,
        ]);

        if ($repeatWeekly) {
            $this->createWeeklyRecurrence($appointment, $psychologist, $patient, $repeatUntil);
        }

        $this->googleCalendarService->syncAppointment($appointment);

        return response()->json($appointment->load('patient', 'recurrence'), 201);
    }

    public function update(AppointmentUpdateRequest $request, int $id)
    {
        $psychologistId = $this->psychologistId($request);

        $appointment = Appointment::where('psychologist_id', $psychologistId)
            ->findOrFail($id);

        $data = $request->validated();
        $appointment->loadMissing('patient');
        $patient = $appointment->patient;

        if (isset($data['patient_id'])) {
            $patient = $this->findOwnedPatient($data['patient_id'], $psychologistId);
        }

        if (isset($data['start_at']) || isset($data['end_at'])) {
            $start = Carbon::parse($data['start_at'] ?? $appointment->start_at);
            $end = Carbon::parse($data['end_at'] ?? $appointment->end_at);
            $this->ensureValidRange($start, $end);
            $this->ensureNoOverlap($psychologistId, $start, $end, $appointment->id);
            $data['start_at'] = $start;
            $data['end_at'] = $end;
        }

        if (!array_key_exists('price', $data) && $patient) {
            $this->applyPatientFee($data, $patient);
        }

        $appointment->update($data);

        $this->googleCalendarService->syncAppointment($appointment);

        return response()->json($appointment->refresh()->load('patient', 'recurrence'));
    }

    public function cancel(Request $request, int $id)
    {
        $appointment = $this->findOwnedAppointment($request, $id);
        $appointment->update(['status' => 'canceled']);

        $this->googleCalendarService->deleteAppointment($appointment);

        return response()->json($appointment->refresh()->load('patient', 'recurrence'));
    }

    public function markDone(Request $request, int $id)
    {
        $appointment = $this->findOwnedAppointment($request, $id);
        $appointment->update(['status' => 'done']);

        $this->googleCalendarService->syncAppointment($appointment);

        return response()->json($appointment->refresh()->load('patient', 'recurrence'));
    }

    public function markMissed(Request $request, int $id)
    {
        $appointment = $this->findOwnedAppointment($request, $id);
        $appointment->update(['status' => 'missed']);

        $this->googleCalendarService->syncAppointment($appointment);

        return response()->json($appointment->refresh()->load('patient', 'recurrence'));
    }

    private function createWeeklyRecurrence(Appointment $appointment, Psychologist $psychologist, Patient $patient, ?string $repeatUntil = null): void
    {
        $timezone = $psychologist->timezone ?? config('app.timezone');
        $startLocal = $appointment->start_at->copy()->setTimezone($timezone);
        $endLocal = $appointment->end_at->copy()->setTimezone($timezone);
        $duration = max(1, $startLocal->diffInMinutes($endLocal));

        $untilDate = null;
        if ($repeatUntil) {
            try {
                $untilDate = Carbon::parse($repeatUntil, $timezone)->startOfDay();
            } catch (\Throwable) {
                abort(422, 'A data final da recorrência é inválida.');
            }
        }

        if ($untilDate && $untilDate->lt($startLocal->copy()->startOfDay())) {
            abort(422, 'A data final da recorrência deve ser igual ou posterior à primeira sessão.');
        }

        $recurrence = RecurringAppointment::create([
            'psychologist_id' => $psychologist->id,
            'patient_id' => $patient->id,
            'weekday' => (int) $startLocal->dayOfWeek,
            'start_time' => $startLocal->format('H:i:s'),
            'session_duration' => $duration,
            'type' => $appointment->type ?? 'online',
            'price' => $appointment->price,
            'start_date' => $startLocal->toDateString(),
            'end_date' => $untilDate?->toDateString(),
            'timezone' => $timezone,
            'status' => 'active',
        ]);

        $appointment->update([
            'recurrence_id' => $recurrence->id,
            'occurrence_date' => $startLocal->toDateString(),
        ]);

        $this->recurringAppointmentService->generateUpcomingOccurrences($recurrence);
    }

    private function findOwnedAppointment(Request $request, int $id): Appointment
    {
        $psychologistId = $this->psychologistId($request);

        return Appointment::where('psychologist_id', $psychologistId)
            ->findOrFail($id);
    }

    private function parseDate(string $value, string $field): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            abort(422, sprintf('Parâmetro "%s" inválido.', $field));
        }
    }

    private function findOwnedPatient(int $patientId, int $psychologistId): Patient
    {
        return Patient::where('psychologist_id', $psychologistId)
            ->findOrFail($patientId);
    }

    private function applyPatientFee(array &$data, Patient $patient): void
    {
        $price = $this->calculateSessionFee($patient);
        if ($price !== null) {
            $data['price'] = $price;
        }
    }

    private function calculateSessionFee(Patient $patient): ?float
    {
        $value = $patient->session_fee_value;

        if ($value === null) {
            return null;
        }

        return match ($patient->session_fee_type) {
            'biweekly' => round($value / 2, 2),
            'monthly' => round($value / 4, 2),
            default => (float) $value,
        };
    }
}
