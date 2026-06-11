<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancePaymentUpdateRequest;
use App\Http\Requests\FinanceSettingsUpdateRequest;
use App\Models\Appointment;
use App\Models\Psychologist;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    private const CHARGEABLE_STATUSES = ['scheduled', 'done', 'missed'];

    private const PAYMENT_METHOD_LABELS = [
        'pix' => 'Pix',
        'credit_card' => 'Cartão de crédito',
        'debit_card' => 'Cartão de débito',
        'cash' => 'Dinheiro',
        'bank_transfer' => 'Transferência',
        'insurance' => 'Convênio',
        'other' => 'Outro',
    ];

    private const STATUS_LABELS = [
        'scheduled' => 'Agendado',
        'done' => 'Concluído',
        'missed' => 'Faltou',
        'canceled' => 'Cancelado',
    ];

    public function dashboard(Request $request)
    {
        $psychologist = $this->psychologist($request);
        $timezone = $psychologist->timezone ?? config('app.timezone');
        [$monthStartLocal, $monthEndLocal] = $this->resolveMonthRange($request, $timezone);
        $monthStart = $monthStartLocal->copy()->timezone(config('app.timezone'));
        $monthEnd = $monthEndLocal->copy()->timezone(config('app.timezone'));
        $todayLocal = Carbon::now($timezone)->startOfDay();

        $monthAppointments = Appointment::query()
            ->with('patient:id,name,email,phone,cpf,status')
            ->where('psychologist_id', $psychologist->id)
            ->whereIn('status', self::CHARGEABLE_STATUSES)
            ->whereBetween('start_at', [$monthStart, $monthEnd])
            ->orderBy('start_at')
            ->get();

        $openAppointments = Appointment::query()
            ->with('patient:id,name,email,phone,cpf,status')
            ->where('psychologist_id', $psychologist->id)
            ->whereIn('status', self::CHARGEABLE_STATUSES)
            ->whereNull('paid_at')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->where('start_at', '<=', $monthEnd)
            ->orderBy('start_at')
            ->get();

        $walletSource = $monthAppointments
            ->concat($openAppointments)
            ->unique('id')
            ->values();

        $receivables = $openAppointments
            ->map(fn (Appointment $appointment) => $this->serializeReceivable($appointment, $psychologist, $todayLocal))
            ->sortBy([
                ['days_overdue', 'desc'],
                ['due_at', 'asc'],
                ['start_at', 'asc'],
            ])
            ->values();

        $paidAppointments = $monthAppointments
            ->filter(fn (Appointment $appointment) => $appointment->paid_at !== null)
            ->sortByDesc('paid_at')
            ->map(fn (Appointment $appointment) => $this->serializeReceivable($appointment, $psychologist, $todayLocal))
            ->values();

        return response()->json([
            'period' => [
                'month' => $monthStartLocal->format('Y-m'),
                'label' => Str::ucfirst($monthStartLocal->translatedFormat('F Y')),
                'from' => $monthStartLocal->toDateString(),
                'to' => $monthEndLocal->toDateString(),
            ],
            'settings' => $this->serializeSettings($psychologist),
            'summary' => $this->buildSummary($monthAppointments, $openAppointments, $psychologist, $todayLocal, $monthEndLocal),
            'wallet' => $this->buildWallet($walletSource, $psychologist, $todayLocal),
            'receivables' => $receivables,
            'paid_appointments' => $paidAppointments,
            'forecast' => $this->buildForecast($psychologist, $monthStartLocal),
            'recent_receipts' => $this->recentReceipts($psychologist),
            'payment_methods' => collect(self::PAYMENT_METHOD_LABELS)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }

    public function updateSettings(FinanceSettingsUpdateRequest $request)
    {
        $psychologist = $this->psychologist($request);
        $data = $request->validated();

        $psychologist->fill([
            'pix_key_type' => $data['pix_key_type'] ?? null,
            'pix_key' => $this->nullableTrim($data['pix_key'] ?? null),
            'default_payment_link' => $this->nullableTrim($data['default_payment_link'] ?? null),
            'receipt_prefix' => strtoupper($this->nullableTrim($data['receipt_prefix'] ?? null) ?: 'REC'),
            'payment_terms_days' => (int) $data['payment_terms_days'],
            'charge_message_template' => $this->nullableTrim($data['charge_message_template'] ?? null),
        ]);
        $psychologist->save();

        return response()->json([
            'settings' => $this->serializeSettings($psychologist->fresh()),
        ]);
    }

    public function updatePayment(FinancePaymentUpdateRequest $request, int $appointmentId)
    {
        $psychologist = $this->psychologist($request);
        $appointment = $this->ownedAppointment($psychologist, $appointmentId);
        $data = $request->validated();

        $updates = [];

        foreach (['price', 'payment_method', 'payment_link', 'payment_notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = is_string($data[$field] ?? null)
                    ? $this->nullableTrim($data[$field])
                    : $data[$field];
            }
        }

        if (array_key_exists('payment_due_at', $data)) {
            $updates['payment_due_at'] = $data['payment_due_at']
                ? Carbon::parse($data['payment_due_at'])->toDateString()
                : null;
        }

        if (array_key_exists('paid', $data)) {
            $updates['paid_at'] = $data['paid']
                ? Carbon::parse($data['paid_at'] ?? now())
                : null;
        } elseif (array_key_exists('paid_at', $data)) {
            $updates['paid_at'] = $data['paid_at'] ? Carbon::parse($data['paid_at']) : null;
        }

        $appointment->update($updates);

        return response()->json([
            'appointment' => $this->serializeReceivable(
                $appointment->refresh()->load('patient:id,name,email,phone,cpf,status'),
                $psychologist,
                Carbon::now($psychologist->timezone ?? config('app.timezone'))->startOfDay()
            ),
        ]);
    }

    public function issueReceipt(Request $request, int $appointmentId)
    {
        $psychologist = $this->psychologist($request);
        $appointment = $this->ownedAppointment($psychologist, $appointmentId)
            ->load('patient:id,name,email,phone,cpf,status');

        abort_if(!$appointment->paid_at, 422, 'Marque o atendimento como recebido antes de emitir o recibo.');
        abort_if((float) ($appointment->price ?? 0) <= 0, 422, 'Informe um valor para emitir o recibo.');

        if (!$appointment->receipt_number) {
            $appointment->receipt_number = $this->generateReceiptNumber($psychologist, $appointment);
        }

        if (!$appointment->receipt_issued_at) {
            $appointment->receipt_issued_at = now();
        }

        $appointment->save();

        return response()->json([
            'receipt' => $this->serializeReceipt($appointment->refresh(), $psychologist),
        ]);
    }

    private function psychologist(Request $request): Psychologist
    {
        $user = $request->user()->loadMissing('psychologist');
        $psychologist = $user->psychologist;

        abort_if(!$psychologist, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        return $psychologist;
    }

    private function ownedAppointment(Psychologist $psychologist, int $appointmentId): Appointment
    {
        return Appointment::query()
            ->where('psychologist_id', $psychologist->id)
            ->findOrFail($appointmentId);
    }

    private function resolveMonthRange(Request $request, string $timezone): array
    {
        $month = $request->string('month')->toString();

        try {
            $start = $month
                ? Carbon::createFromFormat('Y-m', $month, $timezone)->startOfMonth()
                : Carbon::now($timezone)->startOfMonth();
        } catch (\Throwable) {
            abort(422, 'Informe o mês no formato AAAA-MM.');
        }

        return [$start, $start->copy()->endOfMonth()];
    }

    private function buildSummary(
        Collection $monthAppointments,
        Collection $openAppointments,
        Psychologist $psychologist,
        Carbon $todayLocal,
        Carbon $monthEndLocal
    ): array {
        $expected = $this->sumPrices($monthAppointments);
        $received = $this->sumPrices($monthAppointments->filter(fn (Appointment $appointment) => $appointment->paid_at !== null));
        $pending = $this->sumPrices($monthAppointments->filter(fn (Appointment $appointment) => $appointment->paid_at === null));

        $overdueAppointments = $openAppointments->filter(function (Appointment $appointment) use ($psychologist, $todayLocal) {
            return $this->dueDate($appointment, $psychologist)->lt($todayLocal);
        });

        $forecastAppointments = $monthAppointments->filter(function (Appointment $appointment) use ($todayLocal, $monthEndLocal, $psychologist) {
            $startLocal = $appointment->start_at->copy()->setTimezone($psychologist->timezone ?? config('app.timezone'))->startOfDay();

            return $appointment->paid_at === null
                && $appointment->status === 'scheduled'
                && $startLocal->gte($todayLocal)
                && $startLocal->lte($monthEndLocal);
        });

        return [
            'expected_value' => $this->money($expected),
            'received_value' => $this->money($received),
            'pending_value' => $this->money($pending),
            'overdue_value' => $this->money($this->sumPrices($overdueAppointments)),
            'forecast_value' => $this->money($this->sumPrices($forecastAppointments)),
            'expected_count' => $monthAppointments->count(),
            'received_count' => $monthAppointments->whereNotNull('paid_at')->count(),
            'pending_count' => $monthAppointments->whereNull('paid_at')->count(),
            'overdue_count' => $overdueAppointments->count(),
            'forecast_count' => $forecastAppointments->count(),
            'collection_rate' => $expected > 0 ? round($received / $expected, 4) : null,
        ];
    }

    private function buildWallet(Collection $appointments, Psychologist $psychologist, Carbon $todayLocal): array
    {
        return $appointments
            ->groupBy('patient_id')
            ->map(function (Collection $items) use ($psychologist, $todayLocal) {
                /** @var Appointment $first */
                $first = $items->first();
                $paid = $items->filter(fn (Appointment $appointment) => $appointment->paid_at !== null);
                $open = $items->filter(fn (Appointment $appointment) => $appointment->paid_at === null);
                $overdue = $open->filter(fn (Appointment $appointment) => $this->dueDate($appointment, $psychologist)->lt($todayLocal));
                $lastAppointment = $items->sortByDesc('start_at')->first();

                return [
                    'patient' => [
                        'id' => $first->patient?->id,
                        'name' => $first->patient?->name ?? 'Paciente sem nome',
                        'email' => $first->patient?->email,
                        'phone' => $first->patient?->phone,
                    ],
                    'appointments' => $items->count(),
                    'total_value' => $this->money($this->sumPrices($items)),
                    'received_value' => $this->money($this->sumPrices($paid)),
                    'open_value' => $this->money($this->sumPrices($open)),
                    'overdue_value' => $this->money($this->sumPrices($overdue)),
                    'overdue_count' => $overdue->count(),
                    'last_appointment_at' => $lastAppointment?->start_at?->toIso8601String(),
                    'status' => $overdue->isNotEmpty() ? 'overdue' : ($open->isNotEmpty() ? 'open' : 'paid'),
                ];
            })
            ->sortByDesc(fn (array $item) => (float) $item['open_value'])
            ->values()
            ->all();
    }

    private function buildForecast(Psychologist $psychologist, Carbon $monthStartLocal): array
    {
        $timezone = $psychologist->timezone ?? config('app.timezone');
        $forecastStartLocal = $monthStartLocal->copy()->startOfMonth();
        $forecastEndLocal = $forecastStartLocal->copy()->addMonths(5)->endOfMonth();

        $appointments = Appointment::query()
            ->where('psychologist_id', $psychologist->id)
            ->whereIn('status', self::CHARGEABLE_STATUSES)
            ->whereBetween('start_at', [
                $forecastStartLocal->copy()->timezone(config('app.timezone')),
                $forecastEndLocal->copy()->timezone(config('app.timezone')),
            ])
            ->get();

        return collect(range(0, 5))->map(function (int $offset) use ($forecastStartLocal, $appointments, $timezone) {
            $start = $forecastStartLocal->copy()->addMonths($offset)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $monthItems = $appointments->filter(function (Appointment $appointment) use ($start, $end, $timezone) {
                $localStart = $appointment->start_at->copy()->setTimezone($timezone);

                return $localStart->betweenIncluded($start, $end);
            });

            $expected = $this->sumPrices($monthItems);
            $received = $this->sumPrices($monthItems->whereNotNull('paid_at'));
            $open = max(0, $expected - $received);

            return [
                'month' => $start->format('Y-m'),
                'label' => Str::ucfirst($start->translatedFormat('M/y')),
                'expected_value' => $this->money($expected),
                'received_value' => $this->money($received),
                'open_value' => $this->money($open),
                'appointments' => $monthItems->count(),
            ];
        })->all();
    }

    private function recentReceipts(Psychologist $psychologist): array
    {
        return Appointment::query()
            ->with('patient:id,name,email,phone,cpf,status')
            ->where('psychologist_id', $psychologist->id)
            ->whereNotNull('receipt_issued_at')
            ->orderByDesc('receipt_issued_at')
            ->limit(6)
            ->get()
            ->map(fn (Appointment $appointment) => $this->serializeReceipt($appointment, $psychologist))
            ->all();
    }

    private function serializeReceivable(Appointment $appointment, Psychologist $psychologist, Carbon $todayLocal): array
    {
        $dueDate = $this->dueDate($appointment, $psychologist);
        $daysOverdue = $appointment->paid_at || $dueDate->gte($todayLocal)
            ? 0
            : (int) floor($dueDate->diffInDays($todayLocal));
        $effectiveLink = $appointment->payment_link ?: $psychologist->default_payment_link;

        return [
            'id' => $appointment->id,
            'patient' => [
                'id' => $appointment->patient?->id,
                'name' => $appointment->patient?->name ?? 'Paciente sem nome',
                'email' => $appointment->patient?->email,
                'phone' => $appointment->patient?->phone,
                'cpf' => $appointment->patient?->cpf,
            ],
            'start_at' => $appointment->start_at?->toIso8601String(),
            'end_at' => $appointment->end_at?->toIso8601String(),
            'status' => $appointment->status,
            'status_label' => self::STATUS_LABELS[$appointment->status] ?? $appointment->status,
            'price' => $this->money($appointment->price),
            'paid_at' => $appointment->paid_at?->toIso8601String(),
            'payment_due_at' => $appointment->payment_due_at?->toDateString(),
            'due_at' => $dueDate->toDateString(),
            'days_overdue' => $daysOverdue,
            'payment_method' => $appointment->payment_method,
            'payment_method_label' => $appointment->payment_method
                ? (self::PAYMENT_METHOD_LABELS[$appointment->payment_method] ?? $appointment->payment_method)
                : null,
            'payment_link' => $appointment->payment_link,
            'effective_payment_link' => $effectiveLink,
            'payment_notes' => $appointment->payment_notes,
            'receipt_number' => $appointment->receipt_number,
            'receipt_issued_at' => $appointment->receipt_issued_at?->toIso8601String(),
            'pix_key' => $psychologist->pix_key,
            'pix_key_type' => $psychologist->pix_key_type,
            'is_paid' => $appointment->paid_at !== null,
            'is_overdue' => $daysOverdue > 0,
        ];
    }

    private function serializeReceipt(Appointment $appointment, Psychologist $psychologist): array
    {
        $timezone = $psychologist->timezone ?? config('app.timezone');

        return [
            'number' => $appointment->receipt_number,
            'issued_at' => $appointment->receipt_issued_at?->toIso8601String(),
            'amount' => $this->money($appointment->price),
            'payment_method' => $appointment->payment_method,
            'payment_method_label' => $appointment->payment_method
                ? (self::PAYMENT_METHOD_LABELS[$appointment->payment_method] ?? $appointment->payment_method)
                : null,
            'appointment' => [
                'id' => $appointment->id,
                'start_at' => $appointment->start_at?->copy()->setTimezone($timezone)->toIso8601String(),
                'end_at' => $appointment->end_at?->copy()->setTimezone($timezone)->toIso8601String(),
                'type' => $appointment->type,
            ],
            'patient' => [
                'name' => $appointment->patient?->name ?? 'Paciente sem nome',
                'email' => $appointment->patient?->email,
                'phone' => $appointment->patient?->phone,
                'cpf' => $appointment->patient?->cpf,
            ],
            'psychologist' => [
                'name' => $psychologist->name,
                'email' => $psychologist->email,
                'phone' => $psychologist->phone,
            ],
            'description' => 'Sessão de psicologia',
        ];
    }

    private function serializeSettings(Psychologist $psychologist): array
    {
        return [
            'pix_key_type' => $psychologist->pix_key_type,
            'pix_key' => $psychologist->pix_key,
            'default_payment_link' => $psychologist->default_payment_link,
            'receipt_prefix' => $psychologist->receipt_prefix ?: 'REC',
            'payment_terms_days' => (int) ($psychologist->payment_terms_days ?? 0),
            'charge_message_template' => $psychologist->charge_message_template,
        ];
    }

    private function dueDate(Appointment $appointment, Psychologist $psychologist): Carbon
    {
        $timezone = $psychologist->timezone ?? config('app.timezone');

        if ($appointment->payment_due_at) {
            return Carbon::parse($appointment->payment_due_at, $timezone)->startOfDay();
        }

        return $appointment->start_at
            ->copy()
            ->setTimezone($timezone)
            ->startOfDay()
            ->addDays((int) ($psychologist->payment_terms_days ?? 0));
    }

    private function generateReceiptNumber(Psychologist $psychologist, Appointment $appointment): string
    {
        $prefix = strtoupper($psychologist->receipt_prefix ?: 'REC');
        $base = sprintf('%s-%s-%06d', $prefix, now()->format('Ymd'), $appointment->id);
        $number = $base;
        $suffix = 2;

        while (
            Appointment::query()
                ->where('psychologist_id', $psychologist->id)
                ->where('receipt_number', $number)
                ->where('id', '!=', $appointment->id)
                ->exists()
        ) {
            $number = sprintf('%s-%d', $base, $suffix);
            $suffix++;
        }

        return $number;
    }

    private function sumPrices(Collection $appointments): float
    {
        return (float) $appointments->sum(fn (Appointment $appointment) => (float) ($appointment->price ?? 0));
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
