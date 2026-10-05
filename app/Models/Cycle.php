<?php

namespace App\Models;

use App\Enums\CycleStage;
use App\Enums\StageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    protected $fillable = ['tahun', 'nama', 'tahap_aktif', 'is_active'];

    protected function casts(): array
    {
        return [
            'tahap_aktif' => CycleStage::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Setiap siklus otomatis punya 5 tahap PPEPP.
        static::created(function (Cycle $cycle) {
            foreach (CycleStage::cases() as $stage) {
                $cycle->stages()->create([
                    'tahap' => $stage,
                    'status' => $stage === CycleStage::Penetapan ? StageStatus::Berjalan : StageStatus::Belum,
                ]);
            }
        });
    }

    /** Siklus yang sedang berjalan. */
    public static function current(): ?self
    {
        return static::where('is_active', true)->latest('tahun')->first();
    }

    public function stages(): HasMany
    {
        return $this->hasMany(CycleStageRecord::class);
    }

    public function selfEvaluations(): HasMany
    {
        return $this->hasMany(SelfEvaluation::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function managementReviews(): HasMany
    {
        return $this->hasMany(ManagementReview::class);
    }

    public function isStageOpen(CycleStage $stage): bool
    {
        return $this->tahap_aktif === $stage;
    }

    /** Tutup tahap aktif & buka tahap berikutnya. False bila sudah tahap terakhir. */
    public function advanceStage(): bool
    {
        $current = $this->tahap_aktif;
        $this->stages()->where('tahap', $current->value)
            ->update(['status' => StageStatus::Selesai->value, 'tanggal_selesai' => now()->toDateString()]);

        $next = $current->next();
        if (! $next) {
            return false;
        }

        $this->stages()->where('tahap', $next->value)
            ->update(['status' => StageStatus::Berjalan->value, 'tanggal_mulai' => now()->toDateString()]);
        $this->update(['tahap_aktif' => $next]);

        return true;
    }
}
