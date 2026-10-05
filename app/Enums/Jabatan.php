<?php

namespace App\Enums;

/** Jabatan baku untuk PIC pernyataan standar & pengguna pengisi. */
enum Jabatan: string
{
    case KetuaUnit = 'ketua_unit';
    case KetuaJurusan = 'ketua_jurusan';
    case KoordinatorProdi = 'koordinator_prodi';
    case Wadir1 = 'wadir_1';
    case Wadir2 = 'wadir_2';
    case Wadir3 = 'wadir_3';
    case Direktur = 'direktur';

    public function label(): string
    {
        return match ($this) {
            self::KetuaUnit => 'Ketua Unit',
            self::KetuaJurusan => 'Ketua Jurusan',
            self::KoordinatorProdi => 'Koordinator Program Studi',
            self::Wadir1 => 'Wakil Direktur I',
            self::Wadir2 => 'Wakil Direktur II',
            self::Wadir3 => 'Wakil Direktur III',
            self::Direktur => 'Direktur',
        };
    }

    /** Tipe unit tempat jabatan ini berada (jabatan tingkat institusi = Institusi). */
    public function unitType(): UnitType
    {
        return match ($this) {
            self::KetuaUnit => UnitType::UnitKerja,
            self::KetuaJurusan => UnitType::Jurusan,
            self::KoordinatorProdi => UnitType::Prodi,
            default => UnitType::Institusi,
        };
    }

    public function isInstitutional(): bool
    {
        return $this->unitType() === UnitType::Institusi;
    }
}
