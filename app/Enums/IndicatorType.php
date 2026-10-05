<?php

namespace App\Enums;

/** Jenis nilai target/capaian sebuah indikator. */
enum IndicatorType: string
{
    case Persen = 'persen';
    case Angka = 'angka';
    case Ada = 'ada';
    case Rupiah = 'rupiah';
    case Teks = 'teks';

    public function label(): string
    {
        return match ($this) {
            self::Persen => 'Persentase (%)',
            self::Angka => 'Angka / jumlah',
            self::Ada => 'Ada / Tersedia',
            self::Rupiah => 'Rupiah',
            self::Teks => 'Kualitatif (teks)',
        };
    }

    public function unit(): ?string
    {
        return match ($this) {
            self::Persen => '%',
            self::Rupiah => 'Rp',
            default => null,
        };
    }
}
