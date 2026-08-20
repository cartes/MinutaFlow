<?php

namespace App\Services\Identification\Drivers;

use App\Enums\IdentificationType;
use App\Services\Identification\Contracts\IdentificationValidatorInterface;

class PeruvianDniValidator implements IdentificationValidatorInterface
{
    public function type(): IdentificationType
    {
        return IdentificationType::PeruvianDni;
    }

    /**
     * Valida el formato del DNI peruano (numérico exactamente de 8 dígitos).
     */
    public function validate(string $value): bool
    {
        $trimmed = trim($value);

        if (!preg_match('/^(\d{8}|\d{1,2}\.?\d{3}\.?\d{3})$/', $trimmed)) {
            return false;
        }

        $clean = $this->clean($trimmed);

        return strlen($clean) === 8 && (int) $clean > 0;
    }

    /**
     * Limpia el DNI quitando puntos, espacios y caracteres no numéricos.
     */
    public function clean(string $value): string
    {
        return preg_replace('/\D/', '', trim($value)) ?? '';
    }

    /**
     * Formatea el DNI peruano a 8 dígitos estándar.
     */
    public function format(string $value): string
    {
        $clean = $this->clean($value);

        if (!ctype_digit($clean) || strlen($clean) !== 8) {
            return trim($value);
        }

        return $clean;
    }

    public function getErrorMessage(): string
    {
        return 'El DNI peruano ingresado debe contener exactamente 8 dígitos numéricos válidos.';
    }
}
