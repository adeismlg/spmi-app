<?php

namespace App\Enums;

enum Jenjang: string
{
    case D2 = 'd2';
    case D3 = 'd3';
    case D4 = 'd4';
    case S2 = 's2';
    case S3 = 's3';

    public function label(): string
    {
        return match ($this) {
            self::D2 => 'D2',
            self::D3 => 'D3',
            self::D4 => 'D4 / Sarjana Terapan',
            self::S2 => 'S2 / Magister Terapan',
            self::S3 => 'S3 / Doktor Terapan',
        };
    }
}
