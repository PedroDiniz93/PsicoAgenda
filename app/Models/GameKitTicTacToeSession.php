<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameKitTicTacToeSession extends Model
{
    protected $table = 'gamekit_tictactoe_sessions';
    protected $fillable = ['psychologist_id', 'patient_id', 'mode', 'status', 'result', 'moves', 'public_token_hash', 'public_token_expires_at', 'started_at', 'finished_at'];
    protected $casts = ['moves' => 'array', 'public_token_expires_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    protected $hidden = ['psychologist_id', 'public_token_hash'];
    public function psychologist(): BelongsTo { return $this->belongsTo(Psychologist::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
}
