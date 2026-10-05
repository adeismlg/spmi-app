<?php

namespace App\Enums;

/** Tiga kelompok standar: 1-8 Pembelajaran, 9-16 Penelitian, 17-24 PkM. */
enum StandardGroup: string
{
    case Pembelajaran = 'pembelajaran';
    case Penelitian = 'penelitian';
    case Pkm = 'pkm';

    public function label(): string
    {
        return match ($this) {
            self::Pembelajaran => 'Pembelajaran',
            self::Penelitian => 'Penelitian',
            self::Pkm => 'Pengabdian kepada Masyarakat',
        };
    }

    public static function fromNomor(int $nomor): self
    {
        return match (true) {
            $nomor <= 8 => self::Pembelajaran,
            $nomor <= 16 => self::Penelitian,
            default => self::Pkm,
        };
    }
}
