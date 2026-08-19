<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;

class TenantManager
{
    private ?string $tenantId = null;
    private ?Tenant $tenant = null;

    /**
     * Obtiene el ID del tenant activo en el contexto actual.
     */
    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    /**
     * Establece el ID del tenant activo.
     */
    public function setTenantId(?string $tenantId): void
    {
        $this->tenantId = $tenantId;
        $this->tenant = null;
    }

    /**
     * Establece el modelo del tenant activo.
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->tenantId = $tenant?->id;
    }

    /**
     * Obtiene la instancia del modelo Tenant activo.
     */
    public function getTenant(): ?Tenant
    {
        if ($this->tenant === null && $this->tenantId !== null) {
            $this->tenant = Tenant::find($this->tenantId);
        }

        return $this->tenant;
    }

    /**
     * Indica si hay un tenant configurado en el contexto actual.
     */
    public function hasTenant(): bool
    {
        return !empty($this->tenantId);
    }

    /**
     * Limpia el contexto de tenant.
     */
    public function clear(): void
    {
        $this->tenantId = null;
        $this->tenant = null;
    }

    /**
     * Ejecuta una rutina temporalmente bajo el contexto de un tenant específico.
     */
    public function runWithTenant(string $tenantId, callable $callback): mixed
    {
        $previousTenantId = $this->tenantId;
        $previousTenant = $this->tenant;

        try {
            $this->setTenantId($tenantId);
            return $callback();
        } finally {
            $this->tenantId = $previousTenantId;
            $this->tenant = $previousTenant;
        }
    }
}
