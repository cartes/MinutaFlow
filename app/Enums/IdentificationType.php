<?php

namespace App\Enums;

enum IdentificationType: string
{
    case ChileanRut = 'CL_RUT';
    case ArgentineDni = 'AR_DNI';

    /**
     * Retorna el nombre legible del tipo de documento.
     */
    public function label(): string
    {
        return match ($this) {
            self::ChileanRut => 'RUT (Chile)',
            self::ArgentineDni => 'DNI (Argentina)',
        };
    }

    /**
     * Retorna el código de país ISO 3166-1 alpha-2 asociado.
     */
    public function countryCode(): string
    {
        return match ($this) {
            self::ChileanRut => 'CL',
            self::ArgentineDni => 'AR',
        };
    }

    /**
     * Resuelve el tipo de identificación por defecto a partir del código de país.
     */
    public static function fromCountry(string $countryCode): self
    {
        return match (strtoupper(trim($countryCode))) {
            'CL', 'CHL', 'CHILE' => self::ChileanRut,
            'AR', 'ARG', 'ARGENTINA' => self::ArgentineDni,
            default => self::ChileanRut,
        };
    }
}
