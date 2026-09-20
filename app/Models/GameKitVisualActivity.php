<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameKitVisualActivity extends Model
{
    protected $table = 'gamekit_visual_activities';

    protected $fillable = [
        'psychologist_id', 'type', 'style', 'theme', 'therapeutic_goal',
        'prompt_version', 'provider', 'model', 'storage_path', 'mime_type',
        'width', 'height', 'status', 'failure_reason', 'public_token_hash',
        'public_token_expires_at', 'public_status',
    ];

    protected $casts = [
        'public_token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'psychologist_id', 'therapeutic_goal', 'prompt_version', 'provider',
        'model', 'storage_path', 'public_token_hash',
    ];

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class);
    }
}
