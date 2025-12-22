<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\Psychologist;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWhatsAppConfirmations extends Command
{
    protected $signature = 'whatsapp:send-confirmations';

    protected $description = 'Envia lembretes automáticos de sessões (WhatsApp/E-mail).';

    public function __construct(
        private readonly WhatsAppService $whatsAppService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $psychologists = Psychologist::query()
            ->where(function ($query) {
                $query->where('whatsapp_confirm_enabled', true)
                    ->orWhere('email_confirm_enabled', true);
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

                if (!$updated) {
                    continue;
                }

                $appointment->save();
            }
        }

        $finalMessage = sprintf(
            'Lembretes enviados - WhatsApp: %d | E-mail: %d',
            $totals['whatsapp'],
            $totals['email']
        );
        $this->info($finalMessage);
        Log::info($finalMessage);

        return self::SUCCESS;
    }

}
