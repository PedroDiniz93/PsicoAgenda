<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $psychologist_id
 * @property int $patient_id
 * @property \Illuminate\Support\Carbon $start_at
 * @property \Illuminate\Support\Carbon $end_at
 * @property string $status
 * @property string $type
 * @property string|null $price
 * @property string|null $meeting_url
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property-read Psychologist|null $psychologist
 * @property-read Patient|null $patient
 * @property-read RecurringAppointment|null $recurrence
 */
class Appointment extends Model
{
    protected $fillable = [
        'psychologist_id',
        'patient_id',
        'start_at',
        'end_at',
        'occurrence_date',
        'status',
        'type',
        'price',
        'paid_at',
        'payment_due_at',
        'payment_method',
        'payment_link',
        'receipt_number',
        'receipt_issued_at',
        'payment_notes',
        'google_event_id',
        'confirmation_sent_at',
        'confirmation_channel',
        'email_reminder_sent_at',
        'sms_reminder_sent_at',
        'meeting_url',
        'meeting_provider',
        'recurrence_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'paid_at' => 'datetime',
        'payment_due_at' => 'date:Y-m-d',
        'receipt_issued_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'email_reminder_sent_at' => 'datetime',
        'sms_reminder_sent_at' => 'datetime',
        'price' => 'decimal:2',
        'occurrence_date' => 'date',
    ];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(RecurringAppointment::class, 'recurrence_id');
    }
}
