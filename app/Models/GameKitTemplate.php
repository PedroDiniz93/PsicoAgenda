<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $psychologist_id
 * @property string $name
 * @property string $format
 * @property string $age_group
 * @property string $theme
 * @property string $activity_type
 * @property string $difficulty
 * @property int|null $duration_minutes
 * @property string $status
 * @property int $current_version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GameKitTemplateCard> $cards
 */
class GameKitTemplate extends Model
{
    protected $table = 'gamekit_templates';

    protected $fillable = ['psychologist_id', 'name', 'format', 'age_group', 'theme', 'activity_type', 'difficulty', 'duration_minutes', 'status', 'current_version'];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(GameKitTemplateCard::class, 'gamekit_template_id');
    }

    public function currentCards(): HasMany
    {
        return $this->cards()->where('version', $this->current_version)->orderBy('position');
    }
}
