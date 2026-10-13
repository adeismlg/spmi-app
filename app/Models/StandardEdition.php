<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StandardEdition extends Model
{
    protected $fillable = ['kode', 'nama', 'tahun_mulai', 'tahun_selesai', 'deskripsi'];

    protected function casts(): array
    {
        return [
            'tahun_mulai' => 'integer',
            'tahun_selesai' => 'integer',
        ];
    }

    public function standards(): HasMany
    {
        return $this->hasMany(Standard::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }

    public function hasCycleData(): bool
    {
        return $this->cycles()->where(function ($query) {
            $query->whereHas('selfEvaluations')->orWhereHas('audits');
        })->exists();
    }

    public function getLabelAttribute(): string
    {
        $period = $this->tahun_mulai.'–'.($this->tahun_selesai ?? 'seterusnya');

        return "{$this->nama} ({$period})";
    }
}
