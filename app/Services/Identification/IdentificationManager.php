<?php

namespace App\Services\Identification;

use App\Enums\IdentificationType;
use App\Services\Identification\Contracts\IdentificationValidatorInterface;
use App\Services\Identification\Drivers\ArgentineDniValidator;
use App\Services\Identification\Drivers\ChileanRutValidator;
use App\Services\Identification\Drivers\ColombianNitValidator;
use App\Services\Identification\Drivers\GenericTaxIdValidator;
use App\Services\Identification\Drivers\PeruvianDniValidator;
use InvalidArgumentException;

class IdentificationManager
{
    /**
     * @var array<string, IdentificationValidatorInterface>
     */
    private array $drivers = [];

    /**
     * @var array<string, callable>
     */
    private array $customCreators = [];

    public function __construct()
    {
        $this->registerDefaultDrivers();
    }

    /**
     * Resuelve el validador correspondiente según tipo o código de país.
     */
    public function driver(IdentificationType|string|null $typeOrCountry = null): IdentificationValidatorInterface
    {
        $type = $this->resolveType($typeOrCountry);
        $key = $type->value;

        if (isset($this->drivers[$key])) {
            return $this->drivers[$key];
        }

        if (isset($this->customCreators[$key])) {
            $driver = ($this->customCreators[$key])();
            $this->drivers[$key] = $driver;
            return $driver;
        }

        throw new InvalidArgumentException("No existe un validador configurado para el tipo [{$key}].");
    }

    /**
     * Registra un validador personalizado para permitir extender con nuevos países.
     */
    public function extend(IdentificationType|string $typeOrCountry, IdentificationValidatorInterface|callable $driver): self
    {
        $key = $typeOrCountry instanceof IdentificationType ? $typeOrCountry->value : strtoupper(trim($typeOrCountry));

        if ($driver instanceof IdentificationValidatorInterface) {
            $this->drivers[$key] = $driver;
        } else {
            $this->customCreators[$key] = $driver;
        }

        return $this;
    }

    /**
     * Atajo para validar directamente un valor.
     */
    public function validate(string $value, IdentificationType|string|null $typeOrCountry = null): bool
    {
        return $this->driver($typeOrCountry)->validate($value);
    }

    /**
     * Atajo para limpiar un valor de identificación.
     */
    public function clean(string $value, IdentificationType|string|null $typeOrCountry = null): string
    {
        return $this->driver($typeOrCountry)->clean($value);
    }

    /**
     * Atajo para formatear un valor de identificación.
     */
    public function format(string $value, IdentificationType|string|null $typeOrCountry = null): string
    {
        return $this->driver($typeOrCountry)->format($value);
    }

    /**
     * Resuelve el enum IdentificationType a partir de un string, enum o null (por defecto CL).
     */
    public function resolveType(IdentificationType|string|null $typeOrCountry): IdentificationType
    {
        if ($typeOrCountry === null) {
            return IdentificationType::ChileanRut;
        }

        if ($typeOrCountry instanceof IdentificationType) {
            return $typeOrCountry;
        }

        $normalized = strtoupper(trim($typeOrCountry));

        // Intenta coincidir con valor directo del enum
        $fromValue = IdentificationType::tryFrom($normalized);
        if ($fromValue !== null) {
            return $fromValue;
        }

        // Intenta coincidir por código de país
        return IdentificationType::fromCountry($normalized);
    }

    /**
     * Inicializa los drivers predefinidos del sistema.
     */
    private function registerDefaultDrivers(): void
    {
        $this->drivers[IdentificationType::ChileanRut->value] = new ChileanRutValidator();
        $this->drivers[IdentificationType::ArgentineDni->value] = new ArgentineDniValidator();
        $this->drivers[IdentificationType::PeruvianDni->value] = new PeruvianDniValidator();
        $this->drivers[IdentificationType::ColombianNit->value] = new ColombianNitValidator();
        $this->drivers[IdentificationType::Generic->value] = new GenericTaxIdValidator();
    }
}
