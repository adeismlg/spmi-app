<?php

namespace App\Models;

use App\Enums\AuditStatus;
use App\Enums\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    protected $fillable = [
        'cycle_id', 'unit_id', 'semester', 'tanggal_desk_evaluation',
        'tanggal_visitasi', 'status', 'ringkasan',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuditStatus::class,
            'semester' => Semester::class,
            'tanggal_desk_evaluation' => 'date',
            'tanggal_visitasi' => 'date',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function auditors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_auditors')->withPivot('peran')->withTimestamps();
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(AuditChecklist::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function hasAuditor(User $user): bool
    {
        return $this->auditors()->where('users.id', $user->id)->exists();
    }
}
