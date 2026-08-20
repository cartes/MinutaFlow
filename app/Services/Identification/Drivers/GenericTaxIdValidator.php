<?php

namespace App\Services\Identification\Drivers;

use App\Enums\IdentificationType;
use App\Services\Identification\Contracts\IdentificationValidatorInterface;

class GenericTaxIdValidator implements IdentificationValidatorInterface
{
    public function type(): IdentificationType
    {
        return IdentificationType::Generic;
    }

    /**
     * Valida un identificador general o pasaporte internacional:
     * - Alfanumérico con guiones y puntos permitidos
     * - Longitud entre 4 y 30 caracteres
     * - Sin caracteres de inyección o control
     */
    public function validate(string $value): bool
    {
        $trimmed = trim($value);

        if (strlen($trimmed) < 4 || strlen($trimmed) > 30) {
            return false;
        }

        // Permite únicamente letras, números, puntos, guiones y guiones bajos
        return (bool) preg_match('/^[-A-Za-z0-9._]{4,30}$/', $trimmed);
    }

    /**
     * Limpia el identificador quitando espacios externos y caracteres de control.
     */
    public function clean(string $value): string
    {
        $trimmed = trim($value);
        return strtoupper((string) preg_replace('/[^-A-Za-z0-9._]/', '', $trimmed));
    }

    /**
     * Formatea el valor en mayúsculas estandarizadas.
     */
    public function format(string $value): string
    {
        return $this->clean($value);
    }

    public function getErrorMessage(): string
    {
        return 'El documento de identificación o pasaporte internacional ingresado no es válido.';
    }
}
