<?php

namespace App\Services;

use App\Enums\IndicatorType;
use App\Models\IndicatorTarget;

class ScoreService
{
    /** Skor 0-100 dari capaian terhadap target. Null bila tidak dapat dihitung otomatis. */
    public static function score(IndicatorType $tipe, ?float $capaian, ?IndicatorTarget $target): ?float
    {
        if ($capaian === null) {
            return null;
        }

        // Indikator "ada/tersedia": capaian 1 = ada, 0 = tidak ada.
        if ($tipe === IndicatorType::Ada) {
            return $capaian >= 1 ? 100.0 : 0.0;
        }

        // Kualitatif: skor diisi manual oleh pengguna.
        if ($tipe === IndicatorType::Teks || ! $target || $target->nilai === null || $target->nilai <= 0) {
            return null;
        }

        // Operator "<" / "<=": makin kecil makin baik.
        if (in_array($target->operator, ['<', '<='], true)) {
            return $capaian <= $target->nilai
                ? 100.0
                : round(max(0, $target->nilai / max($capaian, 0.0001) * 100), 2);
        }

        return round(min(100, $capaian / $target->nilai * 100), 2);
    }

    /** @deprecated Gunakan score(). Dipertahankan untuk kompatibilitas. */
    public static function fromCapaian(?float $capaian, ?float $target): ?float
    {
        if ($capaian === null || $target === null || $target <= 0) {
            return null;
        }

        return round(min(100, $capaian / $target * 100), 2);
    }
}
