<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychologistAvailabilityRule extends Model
{
    protected $fillable = [
        'psychologist_id',
        'weekday',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_active' => 'boolean',
    ];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }
}
