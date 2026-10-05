<?php

namespace App\Enums;

enum DocumentType: string
{
    case Kebijakan = 'kebijakan';
    case Manual = 'manual';
    case Standar = 'standar';
    case Formulir = 'formulir';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
