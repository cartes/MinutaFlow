<?php

namespace App\Services\Identification\Drivers;

use App\Enums\IdentificationType;
use App\Services\Identification\Contracts\IdentificationValidatorInterface;

class ArgentineDniValidator implements IdentificationValidatorInterface
{
    public function type(): IdentificationType
    {
        return IdentificationType::ArgentineDni;
    }

    /**
     * Valida el formato del DNI argentino (numérico entre 7 y 8 dígitos).
     */
    public function validate(string $value): bool
    {
        $clean = $this->clean($value);

        if (!ctype_digit($clean)) {
            return false;
        }

        $length = strlen($clean);
        $number = (int) $clean;

        return ($length === 7 || $length === 8) && $number > 0;
    }

    /**
     * Limpia el DNI quitando puntos, espacios y caracteres no numéricos.
     */
    public function clean(string $value): string
    {
        return preg_replace('/\D/', '', trim($value)) ?? '';
    }

    /**
     * Formatea el DNI con puntos separadores de miles (ej: 12.345.678).
     */
    public function format(string $value): string
    {
        $clean = $this->clean($value);

        if (!ctype_digit($clean) || $clean === '') {
            return trim($value);
        }

        return number_format((int) $clean, 0, '', '.');
    }

    public function getErrorMessage(): string
    {
        return 'El DNI argentino ingresado no es válido.';
    }
}
