<?php

namespace App\Enums;

enum Conformity: string
{
    case Sesuai = 'sesuai';
    case Observasi = 'observasi';
    case KtsMinor = 'kts_minor';
    case KtsMajor = 'kts_major';

    public function label(): string
    {
        return match ($this) {
            self::Sesuai => 'Sesuai',
            self::Observasi => 'Observasi',
            self::KtsMinor => 'KTS Minor',
            self::KtsMajor => 'KTS Major',
        };
    }

    public function isFinding(): bool
    {
        return $this !== self::Sesuai;
    }

    public function badge(): string
    {
        return match ($this) {
            self::Sesuai => 'success',
            self::Observasi => 'info',
            self::KtsMinor => 'warning',
            self::KtsMajor => 'danger',
        };
    }
}
