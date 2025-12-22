<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\Psychologist;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWhatsAppConfirmations extends Command
{
    protected $signature = 'whatsapp:send-confirmations';

    protected $description = 'Envia lembretes automáticos de sessões (WhatsApp/E-mail/SMS).';

    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly SmsService $smsService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $psychologists = Psychologist::query()
            ->where(function ($query) {
                $query->where('whatsapp_confirm_enabled', true)
                    ->orWhere('email_confirm_enabled', true)
                    ->orWhere('sms_confirm_enabled', true);
            })
            ->get();

        if ($psychologists->isEmpty()) {
            $message = 'Nenhum psicólogo com lembretes automáticos habilitados.';
            $this->info($message);
            Log::info($message);

            return self::SUCCESS;
        }

        $totals = [
            'whatsapp' => 0,
            'email' => 0,
            'sms' => 0,
        ];

        foreach ($psychologists as $psychologist) {
            $daysBefore = max(0, (int) $psychologist->whatsapp_confirm_days_before);

            $tzNow = Carbon::now($psychologist->timezone ?? config('app.timezone'));
            $targetStart = $tzNow->copy()->addDays($daysBefore)->startOfDay()->timezone(config('app.timezone'));
            $targetEnd = $tzNow->copy()->addDays($daysBefore)->endOfDay()->timezone(config('app.timezone'));

            $appointments = Appointment::query()
                ->with(['patient', 'psychologist'])
                ->where('psychologist_id', $psychologist->id)
                ->where('status', 'scheduled')
                ->whereBetween('start_at', [$targetStart, $targetEnd])
                ->orderBy('start_at')
                ->get();

            Log::info('Processando lembretes automáticos', [
                'psychologist_id' => $psychologist->id,
                'psychologist_name' => $psychologist->name,
                'appointments_count' => $appointments->count(),
                'days_before' => $daysBefore,
                'window_start' => $targetStart->toIso8601String(),
                'window_end' => $targetEnd->toIso8601String(),
            ]);

            foreach ($appointments as $appointment) {
                $updated = false;

                if (
                    $psychologist->whatsapp_confirm_enabled
                    && !$appointment->confirmation_sent_at
                    && $this->whatsAppService->sendSessionConfirmation($appointment)
                ) {
                    $appointment->confirmation_sent_at = now();
                    $appointment->confirmation_channel = 'whatsapp';
                    $totals['whatsapp']++;
                    $updated = true;
                    Log::info('Confirmação enviada com sucesso via WhatsApp', ['appointment_id' => $appointment->id]);
                }

                if (
                    $psychologist->email_confirm_enabled
                    && !$appointment->email_reminder_sent_at
                    && $appointment->patient?->email
                ) {
                    Mail::to($appointment->patient->email)->send(new AppointmentReminderMail($appointment));
                    $appointment->email_reminder_sent_at = now();
                    $totals['email']++;
                    $updated = true;
                    Log::info('Lembrete enviado por e-mail', ['appointment_id' => $appointment->id]);
                }

                if (
                    $psychologist->sms_confirm_enabled
                    && !$appointment->sms_reminder_sent_at
                    && ($formattedPhone = $this->formatPhoneNumber($appointment->patient?->phone))
                ) {
                    $smsMessage = $this->buildSmsMessage($appointment);
                    if ($this->smsService->send($formattedPhone, $smsMessage)) {
                        $appointment->sms_reminder_sent_at = now();
                        $totals['sms']++;
                        $updated = true;
                        Log::info('Lembrete enviado por SMS', ['appointment_id' => $appointment->id]);
                    }
                }

                if (!$updated) {
                    continue;
                }

                $appointment->save();
            }
        }

        $finalMessage = sprintf(
            'Lembretes enviados - WhatsApp: %d | E-mail: %d | SMS: %d',
            $totals['whatsapp'],
            $totals['email'],
            $totals['sms']
        );
        $this->info($finalMessage);
        Log::info($finalMessage);

        return self::SUCCESS;
    }

    private function formatPhoneNumber(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (!$digits) {
            return null;
        }

        $digits = ltrim($digits, '0');

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return '+' . $digits;
        }

        if (strlen($digits) >= 10) {
            return '+55' . $digits;
        }

        return null;
    }

    private function buildSmsMessage(Appointment $appointment): string
    {
        $patientName = $appointment->patient?->name ?? 'Paciente';
        $psychologistName = $appointment->psychologist?->name ?? config('app.name');
        $timezone = $appointment->psychologist?->timezone ?? config('app.timezone');

        $startAt = $appointment->start_at instanceof Carbon
            ? $appointment->start_at->copy()
            : Carbon::parse($appointment->start_at);
        $startAt->setTimezone($timezone);

        return sprintf(
            'Olá %s! Lembrete: sessão com %s em %s às %s. Caso precise reagendar, nos avise.',
            $patientName,
            $psychologistName,
            $startAt->format('d/m/Y'),
            $startAt->format('H:i')
        );
    }
}
