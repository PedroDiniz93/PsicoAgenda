<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameKitTemplateCard extends Model
{
    protected $table = 'gamekit_template_cards';
    protected $fillable = ['gamekit_template_id', 'version', 'position', 'context', 'question', 'options'];
    protected $casts = ['options' => 'array'];
    public function template(): BelongsTo { return $this->belongsTo(GameKitTemplate::class, 'gamekit_template_id'); }
}
