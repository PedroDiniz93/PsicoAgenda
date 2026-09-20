<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OnlineSession extends Model
{
    protected $fillable = [
        'psychologist_id',
        'appointment_id',
        'patient_id',
        'token_hash',
        'status',
        'expires_at',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $hidden = [
        'token_hash',
    ];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function createWithToken(array $attributes, Carbon $expiresAt): array
    {
        $token = Str::random(64);

        $session = static::create([
            ...$attributes,
            'token_hash' => static::hashToken($token),
            'expires_at' => $expiresAt,
        ]);

        return [$session, $token];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function refreshStatus(): self
    {
        if ($this->status !== 'ended' && $this->isExpired()) {
            $this->forceFill(['status' => 'expired'])->save();
        }

        return $this;
    }
}
