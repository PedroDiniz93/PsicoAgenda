<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameKitMemoryResult extends Model
{
    protected $table = 'gamekit_memory_results';
    protected $fillable = ['gamekit_memory_session_id', 'attempts', 'matched_pairs', 'duration_seconds', 'completed', 'completed_at'];
    protected $casts = ['completed' => 'boolean', 'completed_at' => 'datetime'];

    public function session(): BelongsTo { return $this->belongsTo(GameKitMemorySession::class, 'gamekit_memory_session_id'); }
}
