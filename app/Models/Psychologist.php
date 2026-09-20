<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $timezone
 * @property int|null $session_duration
 * @property int|null $daily_appointment_limit
 * @property bool $allow_online
 * @property bool $allow_in_person
 * @property bool $whatsapp_confirm_enabled
 * @property int|null $whatsapp_confirm_days_before
 * @property string|null $whatsapp_sender_phone_id
 * @property string|null $whatsapp_sender_display_number
 * @property bool $email_confirm_enabled
 * @property bool $sms_confirm_enabled
 * @property-read User|null $user
 */
class Psychologist extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'timezone',
        'session_duration',
        'daily_appointment_limit',
        'allow_online',
        'allow_in_person',
        'google_calendar_token',
        'whatsapp_confirm_enabled',
        'whatsapp_confirm_days_before',
        'whatsapp_sender_phone_id',
        'whatsapp_sender_display_number',
        'email_confirm_enabled',
        'theme_mode',
        'sms_confirm_enabled',
        'pix_key_type',
        'pix_key',
        'default_payment_link',
        'receipt_prefix',
        'payment_terms_days',
        'charge_message_template',
    ];

    protected $casts = [
        'allow_online' => 'boolean',
        'allow_in_person' => 'boolean',
        'whatsapp_confirm_enabled' => 'boolean',
        'whatsapp_confirm_days_before' => 'integer',
        'email_confirm_enabled' => 'boolean',
        'theme_mode' => 'string',
        'sms_confirm_enabled' => 'boolean',
        'daily_appointment_limit' => 'integer',
        'payment_terms_days' => 'integer',
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

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(PsychologistAvailabilityRule::class);
    }

    public function scheduleBlocks(): HasMany
    {
        return $this->hasMany(PsychologistScheduleBlock::class);
    }

    public function patientRecords(): HasMany
    {
        return $this->hasMany(PatientRecord::class);
    }

    public function getGoogleCalendarConnectedAttribute(): bool
    {
        return ! empty($this->attributes['google_calendar_token'] ?? null);
    }
}
