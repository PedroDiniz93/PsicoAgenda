<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameKitRoutineBlock extends Model
{
    protected $table = 'gamekit_routine_blocks';
    protected $fillable = ['gamekit_routine_version_id', 'position', 'start_time', 'duration_minutes', 'title', 'description', 'category', 'icon'];
    public function version(): BelongsTo { return $this->belongsTo(GameKitRoutineVersion::class, 'gamekit_routine_version_id'); }
}
