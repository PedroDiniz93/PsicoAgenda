<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Psychologist extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'timezone',
        'session_duration',
        'allow_online',
        'allow_in_person',
        'google_calendar_token',
        'whatsapp_confirm_enabled',
        'whatsapp_confirm_days_before',
        'email_confirm_enabled',
        'sms_confirm_enabled',
    ];

    protected $casts = [
        'allow_online' => 'boolean',
        'allow_in_person' => 'boolean',
        'whatsapp_confirm_enabled' => 'boolean',
        'whatsapp_confirm_days_before' => 'integer',
        'email_confirm_enabled' => 'boolean',
        'sms_confirm_enabled' => 'boolean',
    ];

    protected $hidden = [
        'google_calendar_token',
    ];

    protected $appends = [
        'google_calendar_connected',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function patientRecords(): HasMany
    {
        return $this->hasMany(PatientRecord::class);
    }

    public function getGoogleCalendarConnectedAttribute(): bool
    {
        return !empty($this->attributes['google_calendar_token'] ?? null);
    }
}
