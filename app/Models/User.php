<?php

namespace App\Models;

use App\Enums\Jabatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'unit_id', 'jabatan'];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'jabatan' => Jabatan::class,
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** Pimpinan/Admin melihat semua unit; peran lain dibatasi. */
    public function seesAllUnits(): bool
    {
        return $this->hasAnyRole(['admin_spmi', 'pimpinan']);
    }

    /** Check if user can be impersonated */
    public function canBeImpersonated(): bool
    {
        // Cannot impersonate admin_spmi or users without email
        return ! $this->hasRole('admin_spmi') && ! is_null($this->email);
    }
}
