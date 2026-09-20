<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $patient_id
 * @property array<string, mixed>|null $payload
 * @property \Illuminate\Support\Carbon|null $triggered_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property-read Patient|null $patient
 */
class PatientAlert extends Model
{
    protected $fillable = [
        'psychologist_id',
        'patient_id',
        'type',
        'title',
        'message',
        'payload',
        'triggered_at',
        'resolved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
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
