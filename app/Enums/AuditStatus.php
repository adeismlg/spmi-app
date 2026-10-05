<?php

namespace App\Enums;

enum AuditStatus: string
{
    case Terjadwal = 'terjadwal';
    case DeskEvaluation = 'desk_evaluation';
    case Visitasi = 'visitasi';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::DeskEvaluation => 'Desk Evaluation',
            default => ucfirst($this->value),
        };
    }
}
