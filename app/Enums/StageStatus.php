<?php

namespace App\Enums;

enum StageStatus: string
{
    case Belum = 'belum';
    case Berjalan = 'berjalan';
    case Selesai = 'selesai';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
