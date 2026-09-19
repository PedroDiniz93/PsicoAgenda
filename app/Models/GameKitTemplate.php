<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameKitTemplate extends Model
{
    protected $table = 'gamekit_templates';
    protected $fillable = ['psychologist_id', 'name', 'format', 'age_group', 'theme', 'activity_type', 'difficulty', 'duration_minutes', 'status', 'current_version'];
    public function psychologist(): BelongsTo { return $this->belongsTo(Psychologist::class); }
    public function cards(): HasMany { return $this->hasMany(GameKitTemplateCard::class, 'gamekit_template_id'); }
    public function currentCards(): HasMany { return $this->cards()->where('version', $this->current_version)->orderBy('position'); }
}
