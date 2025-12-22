<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'psychologist_id',
        'name',
        'phone',
        'email',
        'status',
        'notes',
        'session_fee_type',
        'session_fee_value',
    ];

    protected $casts = [
        'status' => 'string',
        'session_fee_value' => 'decimal:2',
    ];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(PatientRecord::class);
    }
}
