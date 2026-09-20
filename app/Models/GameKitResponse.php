<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameKitResponse extends Model
{
    protected $table = 'gamekit_responses';

    protected $fillable = ['gamekit_session_id', 'gamekit_card_id', 'participant_key', 'answer', 'reviewed', 'include_in_record', 'note'];

    protected $casts = ['reviewed' => 'boolean', 'include_in_record' => 'boolean'];

    protected $hidden = ['participant_key'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameKitSession::class, 'gamekit_session_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(GameKitCard::class, 'gamekit_card_id');
    }
}
