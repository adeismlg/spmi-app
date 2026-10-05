<?php

namespace App\Models;

use App\Enums\CycleStage;
use App\Enums\StageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tabel `cycle_stages` (dinamai *Record agar tidak bentrok dengan enum CycleStage). */
class CycleStageRecord extends Model
{
    protected $table = 'cycle_stages';

    protected $fillable = ['cycle_id', 'tahap', 'tanggal_mulai', 'tanggal_selesai', 'status'];

    protected function casts(): array
    {
        return [
            'tahap' => CycleStage::class,
            'status' => StageStatus::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }
}
