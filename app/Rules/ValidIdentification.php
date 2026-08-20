<?php

namespace App\Rules;

use App\Enums\IdentificationType;
use App\Models\Company;
use App\Models\Tenant;
use App\Services\Identification\IdentificationManager;
use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIdentification implements ValidationRule
{
    private mixed $countryOrType;

    /**
     * @param IdentificationType|string|Closure|null $countryOrType Tipo, código de país (ej: 'CL', 'AR', 'PE', 'CO') o Closure dinámico.
     */
    public function __construct(mixed $countryOrType = null)
    {
        $this->countryOrType = $countryOrType;
    }

    /**
     * Validador específico para RUT chileno.
     */
    public static function chile(): self
    {
        return new self(IdentificationType::ChileanRut);
    }

    /**
     * Validador específico para DNI argentino.
     */
    public static function argentina(): self
    {
        return new self(IdentificationType::ArgentineDni);
    }

    /**
     * Validador específico para DNI peruano.
     */
    public static function peru(): self
    {
        return new self(IdentificationType::PeruvianDni);
    }

    /**
     * Validador específico para NIT colombiano.
     */
    public static function colombia(): self
    {
        return new self(IdentificationType::ColombianNit);
    }

    /**
     * Validador para documento o pasaporte internacional general.
     */
    public static function generic(): self
    {
        return new self(IdentificationType::Generic);
    }

    /**
     * Validador dinámico según código de país o función de resolución.
     */
    public static function forCountry(string|Closure $country): self
    {
        return new self($country);
    }

    /**
     * Validador contextual que detecta automáticamente el país/tipo de documento según
     * la empresa cliente o el Tenant activo.
     */
    public static function forCurrentContext(Company|string|null $company = null, Tenant|string|null $tenant = null): self
    {
        return new self(function () use ($company, $tenant) {
            return self::resolveContextCountry($company, $tenant);
        });
    }

    /**
     * Resuelve el país del contexto actual según empresa, usuario autenticado, tenant o request.
     */
    public static function resolveContextCountry(Company|string|null $company = null, Tenant|string|null $tenant = null): string|IdentificationType
    {
        // 1. Si se pasó una Company explícita
        if ($company instanceof Company) {
            $companyCountry = $company->settings['country'] ?? $company->settings['identification_type'] ?? null;
            if ($companyCountry) {
                return $companyCountry;
            }
        } elseif (is_string($company) && !empty($company)) {
            $companyModel = Company::find($company);
            $companyCountry = $companyModel?->settings['country'] ?? $companyModel?->settings['identification_type'] ?? null;
            if ($companyCountry) {
                return $companyCountry;
            }
        }

        // 2. Si viene company_id en el request HTTP
        $requestCompanyId = request()?->input('company_id');
        if (!empty($requestCompanyId) && is_string($requestCompanyId)) {
            $companyModel = Company::find($requestCompanyId);
            $companyCountry = $companyModel?->settings['country'] ?? $companyModel?->settings['identification_type'] ?? null;
            if ($companyCountry) {
                return $companyCountry;
            }
        }

        // 3. Revisar Company del usuario autenticado
        $authUser = auth()->user();
        if ($authUser && $authUser->company) {
            $userCompanyCountry = $authUser->company->settings['country'] ?? $authUser->company->settings['identification_type'] ?? null;
            if ($userCompanyCountry) {
                return $userCompanyCountry;
            }
        }

        // 4. Si se pasó un Tenant explícito
        if ($tenant instanceof Tenant) {
            $tenantCountry = $tenant->settings['country'] ?? $tenant->settings['identification_type'] ?? null;
            if ($tenantCountry) {
                return $tenantCountry;
            }
        } elseif (is_string($tenant) && !empty($tenant)) {
            $tenantModel = Tenant::find($tenant);
            $tenantCountry = $tenantModel?->settings['country'] ?? $tenantModel?->settings['identification_type'] ?? null;
            if ($tenantCountry) {
                return $tenantCountry;
            }
        }

        // 5. Revisar Tenant activo en TenantManager o Tenant del usuario
        if (app()->bound(TenantManager::class)) {
            $currentTenant = app(TenantManager::class)->getTenant();
            $tenantCountry = $currentTenant?->settings['country'] ?? $currentTenant?->settings['identification_type'] ?? null;
            if ($tenantCountry) {
                return $tenantCountry;
            }
        }

        if ($authUser && $authUser->tenant) {
            $userTenantCountry = $authUser->tenant->settings['country'] ?? $authUser->tenant->settings['identification_type'] ?? null;
            if ($userTenantCountry) {
                return $userTenantCountry;
            }
        }

        // 6. Revisar si el request especifica país o tipo explícito
        $requestCountry = request()?->input('country') ?? request()?->input('country_code') ?? request()?->input('identification_type');
        if (!empty($requestCountry) && is_string($requestCountry)) {
            return $requestCountry;
        }

        // Fallback estándar (Chile / RUT)
        return config('app.default_country', 'CL');
    }

    /**
     * Ejecuta la regla de validación.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value) && !is_numeric($value)) {
            $fail("El campo {$attribute} debe ser un texto válido.");
            return;
        }

        $resolvedCountryOrType = is_callable($this->countryOrType)
            ? ($this->countryOrType)()
            : ($this->countryOrType ?? self::resolveContextCountry());

        /** @var IdentificationManager $manager */
        $manager = app(IdentificationManager::class);
        $driver = $manager->driver($resolvedCountryOrType);

        if (!$driver->validate((string) $value)) {
            $fail($driver->getErrorMessage());
        }
    }
}
