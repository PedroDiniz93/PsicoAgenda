<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $format
 * @property string $theme
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GameKitCard> $cards
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GameKitResponse> $responses
 */
class GameKitSession extends Model
{
    protected $table = 'gamekit_sessions';

    protected $fillable = [
        'psychologist_id', 'patient_id', 'format', 'age_group', 'theme', 'difficulty',
        'duration_minutes', 'status', 'public_token_hash', 'public_token_expires_at',
        'started_at', 'finished_at',
    ];

    protected $casts = [
        'public_token_expires_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $hidden = ['public_token_hash'];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(GameKitCard::class, 'gamekit_session_id')->orderBy('position');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(GameKitResponse::class, 'gamekit_session_id');
    }
}
