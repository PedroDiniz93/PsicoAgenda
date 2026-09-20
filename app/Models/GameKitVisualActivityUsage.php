<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameKitVisualActivityUsage extends Model
{
    protected $table = 'gamekit_visual_activity_usage';

    protected $fillable = ['psychologist_id', 'week_start', 'generated_count'];

    protected $casts = ['week_start' => 'date', 'generated_count' => 'integer'];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }
}
