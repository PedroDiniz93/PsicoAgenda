<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read GameKitHangmanGame $game
 */
class GameKitHangmanSession extends Model
{
    protected $table = 'gamekit_hangman_sessions';

    protected $fillable = ['gamekit_hangman_game_id', 'psychologist_id', 'patient_id', 'status', 'result', 'public_token_hash', 'public_token_expires_at', 'started_at', 'finished_at'];

    protected $casts = ['result' => 'array', 'public_token_expires_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime'];

    protected $hidden = ['psychologist_id', 'public_token_hash'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(GameKitHangmanGame::class, 'gamekit_hangman_game_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
