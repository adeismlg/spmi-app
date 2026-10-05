<?php

namespace App\Enums;

enum UnitType: string
{
    case Institusi = 'institusi';
    case Fakultas = 'fakultas';
    case Jurusan = 'jurusan';
    case Prodi = 'prodi';
    case UnitKerja = 'unit_kerja';

    public function label(): string
    {
        return match ($this) {
            self::UnitKerja => 'Unit Kerja',
            default => ucfirst($this->value),
        };
    }
}
