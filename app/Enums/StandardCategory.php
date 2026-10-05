<?php

namespace App\Enums;

enum StandardCategory: string
{
    case SnDikti = 'sn_dikti';
    case Pelampauan = 'pelampauan';

    public function label(): string
    {
        return match ($this) {
            self::SnDikti => 'SN-Dikti',
            self::Pelampauan => 'Standar Pelampauan',
        };
    }
}
