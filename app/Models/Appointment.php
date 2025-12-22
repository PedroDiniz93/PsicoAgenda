<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'psychologist_id',
        'patient_id',
        'start_at',
        'end_at',
        'status',
        'type',
        'price',
        'paid_at',
        'google_event_id',
        'confirmation_sent_at',
        'confirmation_channel',
        'email_reminder_sent_at',
        'sms_reminder_sent_at',
        'meeting_url',
        'meeting_provider',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'paid_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'email_reminder_sent_at' => 'datetime',
        'sms_reminder_sent_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
