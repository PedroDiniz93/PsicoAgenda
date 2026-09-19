<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameKitMemorySession extends Model
{
    protected $table = 'gamekit_memory_sessions';
    protected $fillable = ['gamekit_memory_game_id', 'psychologist_id', 'public_token_hash', 'public_token_expires_at', 'status', 'started_at', 'finished_at'];
    protected $hidden = ['public_token_hash'];
    protected $casts = ['public_token_expires_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function game(): BelongsTo { return $this->belongsTo(GameKitMemoryGame::class, 'gamekit_memory_game_id'); }
    public function psychologist(): BelongsTo { return $this->belongsTo(Psychologist::class); }
    public function result(): HasOne { return $this->hasOne(GameKitMemoryResult::class, 'gamekit_memory_session_id'); }
}
