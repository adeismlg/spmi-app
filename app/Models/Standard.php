<?php

namespace App\Models;

use App\Enums\StandardCategory;
use App\Enums\StandardGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Standard extends Model
{
    protected $fillable = ['standard_edition_id', 'kode', 'nomor', 'nama', 'kategori', 'kelompok', 'deskripsi', 'is_active'];

    protected function casts(): array
    {
        return [
            'kategori' => StandardCategory::class,
            'kelompok' => StandardGroup::class,
            'is_active' => 'boolean',
        ];
    }

    public function statements(): HasMany
    {
        return $this->hasMany(Statement::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(StandardEdition::class, 'standard_edition_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }
}
