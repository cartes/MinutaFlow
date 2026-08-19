<?php

namespace App\Services\Identification\Drivers;

use App\Enums\IdentificationType;
use App\Services\Identification\Contracts\IdentificationValidatorInterface;

class ChileanRutValidator implements IdentificationValidatorInterface
{
    public function type(): IdentificationType
    {
        return IdentificationType::ChileanRut;
    }

    /**
     * Valida el formato y el dígito verificador del RUT chileno usando Módulo 11.
     */
    public function validate(string $value): bool
    {
        $cleaned = $this->extractCleanRaw($value);

        if ($cleaned === null || strlen($cleaned) < 2 || strlen($cleaned) > 9) {
            return false;
        }

        $body = substr($cleaned, 0, -1);
        $dv = strtoupper(substr($cleaned, -1));

        if (!ctype_digit($body) || (int) $body === 0) {
            return false;
        }

        return $this->calculateCheckDigit($body) === $dv;
    }

    /**
     * Limpia y estandariza el RUT en formato 'XXXXXXXX-X' (sin puntos, con guión y DV en mayúscula).
     */
    public function clean(string $value): string
    {
        $raw = $this->extractCleanRaw($value);

        if ($raw === null || strlen($raw) < 2) {
            return strtoupper(trim($value));
        }

        $body = substr($raw, 0, -1);
        $dv = strtoupper(substr($raw, -1));

        return "{$body}-{$dv}";
    }

    /**
     * Formatea el RUT con separadores de miles y guión: 'XX.XXX.XXX-X'.
     */
    public function format(string $value): string
    {
        $raw = $this->extractCleanRaw($value);

        if ($raw === null || strlen($raw) < 2) {
            return trim($value);
        }

        $body = substr($raw, 0, -1);
        $dv = strtoupper(substr($raw, -1));

        if (!ctype_digit($body)) {
            return "{$body}-{$dv}";
        }

        $formattedBody = number_format((int) $body, 0, '', '.');

        return "{$formattedBody}-{$dv}";
    }

    public function getErrorMessage(): string
    {
        return 'El RUT chileno ingresado no es válido.';
    }

    /**
     * Calcula el dígito verificador esperado para un cuerpo de RUT dado.
     */
    public function calculateCheckDigit(string $body): string
    {
        $reversed = strrev($body);
        $sum = 0;

        for ($i = 0, $len = strlen($reversed); $i < $len; $i++) {
            $multiplier = ($i % 6) + 2; // Secuencia cíclica 2, 3, 4, 5, 6, 7
            $sum += ((int) $reversed[$i]) * $multiplier;
        }

        $remainder = 11 - ($sum % 11);

        if ($remainder === 11) {
            return '0';
        }

        if ($remainder === 10) {
            return 'K';
        }

        return (string) $remainder;
    }

    /**
     * Extrae sólo los dígitos y el carácter 'K'/'k'.
     */
    private function extractCleanRaw(string $value): ?string
    {
        $filtered = preg_replace('/[^\dkK]/', '', trim($value));

        return $filtered !== '' ? $filtered : null;
    }
}
