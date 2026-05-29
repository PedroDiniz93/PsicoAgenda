<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PsychologistEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly User $user,
        private readonly string $code,
        private readonly Carbon $expiresAt
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Código de validação do e-mail')
            ->view('emails.psychologist_email_verification', [
                'user' => $this->user,
                'code' => $this->code,
                'expiresAt' => $this->expiresAt,
            ]);
    }
}
