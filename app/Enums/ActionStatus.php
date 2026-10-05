<?php

namespace App\Enums;

enum ActionStatus: string
{
    case Rencana = 'rencana';
    case Berjalan = 'berjalan';
    case Selesai = 'selesai';
    case Terverifikasi = 'terverifikasi';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
