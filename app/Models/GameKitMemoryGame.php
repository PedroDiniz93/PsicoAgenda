<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameKitMemoryGame extends Model
{
    protected $table = 'gamekit_memory_games';

    protected $fillable = ['psychologist_id', 'name', 'age_group', 'age', 'theme', 'difficulty', 'pair_count', 'status'];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }

    public function pairs(): HasMany
    {
        return $this->hasMany(GameKitMemoryPair::class, 'gamekit_memory_game_id')->orderBy('position');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameKitMemorySession::class, 'gamekit_memory_game_id');
    }
}
