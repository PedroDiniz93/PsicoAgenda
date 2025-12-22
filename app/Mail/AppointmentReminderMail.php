<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Appointment $appointment
    ) {
    }

    public function build(): self
    {
        $psychologist = $this->appointment->psychologist;
        $patient = $this->appointment->patient;
        $timezone = $psychologist?->timezone ?? config('app.timezone');

        $startAt = $this->appointment->start_at instanceof Carbon
            ? $this->appointment->start_at->copy()
            : Carbon::parse($this->appointment->start_at);

        $startAt->setTimezone($timezone);

        return $this
            ->subject('Lembrete de sessão')
            ->view('emails.appointment_reminder', [
                'patient' => $patient,
                'psychologist' => $psychologist,
                'appointment' => $this->appointment,
                'startAt' => $startAt,
            ]);
    }
}
