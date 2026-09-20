<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read GameKitRoutine|null $routine
 */
class GameKitRoutineLink extends Model
{
    protected $table = 'gamekit_routine_links';

    protected $fillable = ['gamekit_routine_id', 'token_hash', 'expires_at', 'status'];

    protected $hidden = ['token_hash'];

    protected $casts = ['expires_at' => 'datetime'];

    public function routine(): BelongsTo
    {
        return $this->belongsTo(GameKitRoutine::class, 'gamekit_routine_id');
    }
}
