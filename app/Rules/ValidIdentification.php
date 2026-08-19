<?php

namespace App\Rules;

use App\Enums\IdentificationType;
use App\Services\Identification\IdentificationManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIdentification implements ValidationRule
{
    private mixed $countryOrType;

    /**
     * @param IdentificationType|string|Closure|null $countryOrType Tipo, código de país (ej: 'CL', 'AR') o Closure dinámico.
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
     * Validador dinámico según código de país.
     */
    public static function forCountry(string|Closure $country): self
    {
        return new self($country);
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
            : $this->countryOrType;

        /** @var IdentificationManager $manager */
        $manager = app(IdentificationManager::class);
        $driver = $manager->driver($resolvedCountryOrType);

        if (!$driver->validate((string) $value)) {
            $fail($driver->getErrorMessage());
        }
    }
}
