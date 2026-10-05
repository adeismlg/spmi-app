<?php

namespace App\Enums;

/** Status usulan perubahan pada sebuah pernyataan standar. */
enum StatementStatus: string
{
    case Aktif = 'aktif';
    case UsulanBaru = 'usulan_baru';
    case UsulanHapus = 'usulan_hapus';
    case Gabungan = 'gabungan';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::UsulanBaru => 'Usulan baru',
            self::UsulanHapus => 'Usulan dihapus',
            self::Gabungan => 'Gabungan',
        };
    }
}
