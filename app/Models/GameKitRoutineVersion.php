<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GameKitRoutineBlock> $blocks
 */
class GameKitRoutineVersion extends Model
{
    protected $table = 'gamekit_routine_versions';

    protected $fillable = ['gamekit_routine_id', 'version', 'summary'];

    public function routine(): BelongsTo
    {
        return $this->belongsTo(GameKitRoutine::class, 'gamekit_routine_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(GameKitRoutineBlock::class, 'gamekit_routine_version_id')->orderBy('position');
    }
}
