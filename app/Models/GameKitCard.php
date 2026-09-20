<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $position
 * @property string $prompt_a
 * @property string $prompt_b
 * @property array<int, string>|null $options
 * @property-read GameKitSession|null $session
 */
class GameKitCard extends Model
{
    protected $table = 'gamekit_cards';

    protected $fillable = ['gamekit_session_id', 'position', 'pair_key', 'prompt_a', 'prompt_b', 'options'];

    protected $casts = ['options' => 'array'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameKitSession::class, 'gamekit_session_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(GameKitResponse::class);
    }
}
