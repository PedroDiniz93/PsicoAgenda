<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameKitMemoryPair extends Model
{
    protected $table = 'gamekit_memory_pairs';

    protected $fillable = ['gamekit_memory_game_id', 'position', 'label_a', 'label_b', 'concept', 'feedback', 'icon', 'accent'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(GameKitMemoryGame::class, 'gamekit_memory_game_id');
    }
}
