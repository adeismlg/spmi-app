<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectImpersonation
{
    /**
     * Deteksi apakah user sedang di-impersonate dan set session flags
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if currently impersonating
        if (session('impersonated_by') && auth()->check()) {
            $request->attributes->set('impersonating', true);
            $request->attributes->set('original_admin_id', session('impersonated_by'));
        }

        return $next($request);
    }
}
