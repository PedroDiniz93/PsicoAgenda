<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'psychologist_id',
        'patient_id',
        'recorded_at',
        'title',
        'notes',
        'treatment_objectives',
        'techniques',
        'homework_items',
        'attachments',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'treatment_objectives' => 'array',
        'techniques' => 'array',
        'homework_items' => 'array',
        'attachments' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }
}
