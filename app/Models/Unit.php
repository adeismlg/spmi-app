<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['kode', 'nama', 'singkatan', 'tipe', 'jenjang', 'parent_id'];

    protected function casts(): array
    {
        return ['tipe' => UnitType::class, 'jenjang' => \App\Enums\Jenjang::class];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function selfEvaluations(): HasMany
    {
        return $this->hasMany(SelfEvaluation::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StatementAssignment::class);
    }
}
