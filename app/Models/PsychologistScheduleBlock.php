<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychologistScheduleBlock extends Model
{
    protected $fillable = [
        'psychologist_id',
        'starts_at',
        'ends_at',
        'type',
        'reason',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }
}
