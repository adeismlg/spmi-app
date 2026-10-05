<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Valid = 'valid';
    case Arsip = 'arsip';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
