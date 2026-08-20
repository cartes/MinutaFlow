<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Valida que el usuario autenticado sea Super Administrador global.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Acceso denegado. Se requieren privilegios de Super Administrador de la plataforma.',
            ], 403);
        }

        return $next($request);
    }
}
