<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Intercepta la petición HTTP y establece el contexto del tenant del usuario autenticado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            $this->tenantManager->setTenantId($user->tenant_id);

            $tenant = $this->tenantManager->getTenant();

            if (!$tenant || !$tenant->is_active) {
                return response()->json([
                    'message' => 'La cuenta de catering no existe o se encuentra inactiva.',
                ], 403);
            }
        }

        return $next($request);
    }
}
