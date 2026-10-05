<?php

namespace App\Models;

use App\Enums\IndicatorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicator extends Model
{
    protected $fillable = [
        'standard_id', 'statement_id', 'kode', 'nama', 'tipe', 'satuan', 'target', 'bobot', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => IndicatorType::class,
            'target' => 'decimal:2',
            'bobot' => 'decimal:2',
        ];
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(Statement::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(IndicatorTarget::class);
    }

    public function selfEvaluations(): HasMany
    {
        return $this->hasMany(SelfEvaluation::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(AuditChecklist::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderBy('urutan')->orderBy('id');
    }

    /** Target untuk tahun siklus tertentu (null bila belum diisi). */
    public function targetFor(int $tahun): ?IndicatorTarget
    {
        return $this->targets->first(fn ($t) => $t->jenis === 'target' && $t->tahun === $tahun);
    }

    public function baseline(): ?IndicatorTarget
    {
        return $this->targets->first(fn ($t) => $t->jenis === 'baseline');
    }

    public function targetLabel(int $tahun): string
    {
        return $this->targetFor($tahun)?->label($this->tipe) ?? '—';
    }

    /** Nilai mentah untuk isian form (">70", "80", "ada"). */
    public function targetInput(string $jenis, int $tahun): string
    {
        $t = $this->targets->first(fn ($x) => $x->jenis === $jenis && $x->tahun === $tahun);

        return $t?->input($this->tipe) ?? '';
    }

    /** Semester tempat indikator dievaluasi pada tahun siklus tertentu. */
    public function semesters(int $year): array
    {
        return $this->statement?->periode_evaluasi?->semesters($year) ?? [2];
    }
}
