<?php

namespace App\Services;

use App\Mail\PsychologistEmailVerificationMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public function send(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addDay();

        $user->forceFill([
            'email_verified_at' => null,
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => $expiresAt,
            'email_verification_sent_at' => now(),
        ])->save();

        Mail::to($user->email)->send(new PsychologistEmailVerificationMail($user, $code, $expiresAt));
    }

    public function ensureActiveCode(User $user): void
    {
        if (!$user->requiresEmailVerification()) {
            return;
        }

        $expiresAt = $user->email_verification_expires_at;

        if ($user->email_verification_code_hash && $expiresAt instanceof Carbon && $expiresAt->isFuture()) {
            return;
        }

        $this->send($user);
    }

    public function verify(User $user, string $code): bool
    {
        if (!$user->requiresEmailVerification()) {
            return true;
        }

        $expiresAt = $user->email_verification_expires_at;

        if (!$user->email_verification_code_hash || !$expiresAt instanceof Carbon || $expiresAt->isPast()) {
            return false;
        }

        if (!Hash::check($code, $user->email_verification_code_hash)) {
            return false;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
            'email_verification_sent_at' => null,
        ])->save();

        return true;
    }
}
