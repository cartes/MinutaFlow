<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * "Boot" del trait para registrar el Global Scope y los hooks de eventos.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenantManager = app(TenantManager::class);
                if ($tenantManager->hasTenant()) {
                    $model->tenant_id = $tenantManager->getTenantId();
                }
            }
        });
    }

    /**
     * Relación con el Tenant al que pertenece el registro.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
