<?php

namespace App\Enums;

enum Semester: int
{
    case Ganjil = 1;
    case Genap = 2;

    public function label(): string
    {
        return $this === self::Ganjil ? 'Semester Ganjil' : 'Semester Genap';
    }

    /** Semester berjalan menurut kalender akademik umum (Feb-Jul = genap). */
    public static function current(?\DateTimeInterface $now = null): self
    {
        $month = (int) ($now ?? now())->format('n');

        return $month >= 2 && $month <= 7 ? self::Genap : self::Ganjil;
    }
}
