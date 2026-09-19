<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameKitRoutine extends Model
{
    protected $table = 'gamekit_routines';
    protected $fillable = ['psychologist_id', 'patient_id', 'name', 'reference_date', 'status', 'current_version'];
    protected $casts = ['reference_date' => 'date:Y-m-d'];
    protected $hidden = ['psychologist_id'];

    public function psychologist(): BelongsTo { return $this->belongsTo(Psychologist::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function versions(): HasMany { return $this->hasMany(GameKitRoutineVersion::class, 'gamekit_routine_id')->orderByDesc('version'); }
    /**
     * A rotina sempre cria versões incrementais e current_version aponta para a
     * última delas. latestOfMany mantém a relação compatível com eager loading
     * (inclusive quando várias rotinas são carregadas na mesma consulta).
     */
    public function currentVersion(): HasOne { return $this->hasOne(GameKitRoutineVersion::class, 'gamekit_routine_id')->latestOfMany('version'); }
    public function links(): HasMany { return $this->hasMany(GameKitRoutineLink::class, 'gamekit_routine_id'); }
}
