<?php

namespace App\Services\Identification\Drivers;

use App\Enums\IdentificationType;
use App\Services\Identification\Contracts\IdentificationValidatorInterface;

class ColombianNitValidator implements IdentificationValidatorInterface
{
    public function type(): IdentificationType
    {
        return IdentificationType::ColombianNit;
    }

    /**
     * Valida el formato y dígito verificador del NIT colombiano según algoritmo oficial DIAN (módulo 11).
     */
    public function validate(string $value): bool
    {
        $trimmed = trim($value);

        if (!preg_match('/^[\d.\s-]+$/', $trimmed)) {
            return false;
        }

        $clean = $this->clean($trimmed);

        if (empty($clean)) {
            return false;
        }

        // Si incluye guión, separamos número base y dígito verificador
        if (str_contains($clean, '-')) {
            [$base, $dv] = explode('-', $clean, 2);
        } else {
            // Si viene todo junto, asumimos los últimos dígitos o validamos longitud 8 a 15
            if (strlen($clean) < 8 || strlen($clean) > 15) {
                return false;
            }
            // Si es puramente numérico sin guión
            if (!ctype_digit($clean)) {
                return false;
            }
            // Si viene sin guión, tomamos el último como DV si la longitud > 8
            $base = substr($clean, 0, -1);
            $dv = substr($clean, -1);
        }

        if (!ctype_digit($base) || !ctype_digit($dv) || strlen($dv) !== 1) {
            return false;
        }

        if (strlen($base) < 6 || strlen($base) > 14) {
            return false;
        }

        return $this->calculateDv($base) === (int) $dv;
    }

    /**
     * Limpia el NIT dejando sólo dígitos y guión separador de DV.
     */
    public function clean(string $value): string
    {
        $trimmed = trim($value);
        $upper = strtoupper($trimmed);

        // Si tiene guión, preservar sólo dígitos y el guión
        if (str_contains($upper, '-')) {
            $parts = explode('-', $upper);
            $base = preg_replace('/\D/', '', $parts[0] ?? '');
            $dv = preg_replace('/\D/', '', $parts[1] ?? '');
            return $base !== '' ? "{$base}-{$dv}" : '';
        }

        return preg_replace('/\D/', '', $upper) ?? '';
    }

    /**
     * Formatea el NIT con puntos y guión separador (ej: 900.123.456-7).
     */
    public function format(string $value): string
    {
        $clean = $this->clean($value);

        if (empty($clean)) {
            return trim($value);
        }

        if (str_contains($clean, '-')) {
            [$base, $dv] = explode('-', $clean, 2);
        } else {
            $base = substr($clean, 0, -1);
            $dv = substr($clean, -1);
        }

        if (!ctype_digit($base) || !ctype_digit($dv)) {
            return trim($value);
        }

        $formattedBase = number_format((float) $base, 0, '', '.');
        return "{$formattedBase}-{$dv}";
    }

    /**
     * Calcula el dígito de verificación oficial según DIAN (Módulo 11 ponderado).
     */
    private function calculateDv(string $nit): int
    {
        $primes = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $nitLength = strlen($nit);
        $sum = 0;

        for ($i = 0; $i < $nitLength; $i++) {
            $digit = (int) $nit[$nitLength - 1 - $i];
            $sum += $digit * $primes[$i];
        }

        $residue = $sum % 11;

        if ($residue === 0 || $residue === 1) {
            return $residue;
        }

        return 11 - $residue;
    }

    public function getErrorMessage(): string
    {
        return 'El NIT colombiano ingresado no es válido o su dígito verificador es incorrecto.';
    }
}
