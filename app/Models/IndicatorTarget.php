<?php

namespace App\Models;

use App\Enums\IndicatorType;
use App\Support\TargetParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorTarget extends Model
{
    public const BASELINE_YEAR = 2025;

    public const TARGET_YEARS = [2026, 2027, 2028, 2029, 2030];

    protected $fillable = ['indicator_id', 'jenis', 'tahun', 'nilai', 'operator', 'teks'];

    protected function casts(): array
    {
        return ['nilai' => 'float'];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function label(?IndicatorType $tipe = null): string
    {
        return TargetParser::format($this->nilai, $this->operator, $this->teks, $tipe ?? $this->indicator->tipe);
    }

    public function input(?IndicatorType $tipe = null): string
    {
        return TargetParser::toInput($this->nilai, $this->operator, $this->teks, $tipe ?? $this->indicator->tipe);
    }
}
