<?php

namespace App\Http\Middleware;

use App\Models\Cycle;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjaga alur PPEPP: route hanya bisa diakses saat tahap siklus aktif sesuai.
 * Pemakaian: ->middleware('stage:pelaksanaan')
 */
class EnsureStageOpen
{
    public function handle(Request $request, Closure $next, string $stage): Response
    {
        $cycle = Cycle::current();

        if (! $cycle || $cycle->tahap_aktif->value !== $stage) {
            $aktif = $cycle ? $cycle->tahap_aktif->label() : 'tidak ada siklus aktif';

            return redirect()->route('dashboard')->with(
                'error',
                'Fitur ini hanya dibuka pada tahap '.ucfirst($stage).". Tahap saat ini: {$aktif}."
            );
        }

        return $next($request);
    }
}
