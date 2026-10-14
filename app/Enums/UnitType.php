<?php

namespace App\Enums;

enum UnitType: string
{
    case Institusi = 'institusi';
    case Fakultas = 'fakultas';
    case Jurusan = 'jurusan';
    case Prodi = 'prodi';
    case UnitKerja = 'unit_kerja';
    case Laboratorium = 'laboratorium';

    public function label(): string
    {
        return match ($this) {
            self::UnitKerja => 'Unit Kerja',
            self::Laboratorium => 'Laboratorium',
            default => ucfirst($this->value),
        };
    }

    public function isAcademic(): bool
    {
        return in_array($this, [self::Prodi, self::Jurusan, self::Fakultas, self::Institusi]);
    }

    public function isLab(): bool
    {
        return $this === self::Laboratorium;
    }
}
