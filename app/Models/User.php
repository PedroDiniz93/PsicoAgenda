<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $email_verification_code_hash
 * @property \Illuminate\Support\Carbon|null $email_verification_expires_at
 * @property \Illuminate\Support\Carbon|null $email_verification_sent_at
 * @property-read Psychologist|null $psychologist
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
        'email_verification_code_hash',
        'email_verification_expires_at',
        'email_verification_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code_hash',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_expires_at' => 'datetime',
        'email_verification_sent_at' => 'datetime',
    ];

    public function psychologist(): HasOne
    {
        return $this->hasOne(Psychologist::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function requiresEmailVerification(): bool
    {
        return $this->role === 'psychologist' && is_null($this->email_verified_at);
    }
}
