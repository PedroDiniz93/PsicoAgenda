<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly User $user,
        private readonly string $resetUrl,
        private readonly Carbon $expiresAt
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Redefinição de senha')
            ->view('emails.password_reset', [
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
                'expiresAt' => $this->expiresAt,
            ]);
    }
}
