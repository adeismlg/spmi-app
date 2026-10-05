<?php

namespace App\Models;

use App\Enums\EvaluationPeriod;
use App\Enums\StatementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\Jabatan;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pernyataan standar (mis. 1.01) — satu baris pada dokumen standar. */
class Statement extends Model
{
    protected $fillable = [
        'standard_id', 'nomor', 'pernyataan',
        'abcd_audience', 'abcd_behaviour', 'abcd_condition', 'abcd_degree',
        'pic', 'pic_jabatan', 'pic_unit', 'jenjang', 'periode_evaluasi', 'referensi', 'status', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'periode_evaluasi' => EvaluationPeriod::class,
            'status' => StatementStatus::class,
            'pic_jabatan' => 'array',
            'pic_unit' => 'array',
            'jenjang' => 'array',
        ];
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StatementAssignment::class);
    }

    /** Label PIC baku, mis. "Ketua Unit, Koordinator Program Studi". */
    public function picLabel(): string
    {
        $labels = collect($this->pic_jabatan ?? [])->map(fn ($j) => Jabatan::tryFrom($j)?->label())->filter();

        return $labels->isEmpty() ? '—' : $labels->implode(', ');
    }
}
