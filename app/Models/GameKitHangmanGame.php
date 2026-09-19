<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameKitHangmanGame extends Model
{
    protected $table = 'gamekit_hangman_games';
    protected $fillable = ['psychologist_id', 'name', 'age_group', 'theme', 'word_count', 'words', 'status'];
    protected $casts = ['words' => 'array'];
    protected $hidden = ['psychologist_id'];
    public function psychologist(): BelongsTo { return $this->belongsTo(Psychologist::class); }
    public function sessions(): HasMany { return $this->hasMany(GameKitHangmanSession::class, 'gamekit_hangman_game_id'); }
}
