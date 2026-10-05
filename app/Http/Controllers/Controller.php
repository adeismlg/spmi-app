<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Opsi select dari enum: [value => label]. */
    protected function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
