<?php

namespace App\Enums;

enum CycleStage: string
{
    case Penetapan = 'penetapan';
    case Pelaksanaan = 'pelaksanaan';
    case Evaluasi = 'evaluasi';
    case Pengendalian = 'pengendalian';
    case Peningkatan = 'peningkatan';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function next(): ?self
    {
        $cases = self::cases();
        $i = array_search($this, $cases, true);

        return $cases[$i + 1] ?? null;
    }
}
