<?php

namespace App\Enums;

enum IdentificationType: string
{
    case ChileanRut = 'CL_RUT';
    case ArgentineDni = 'AR_DNI';
    case PeruvianDni = 'PE_DNI';
    case ColombianNit = 'CO_NIT';
    case MexicanRfc = 'MX_RFC';
    case Generic = 'GENERIC';

    /**
     * Retorna el nombre legible del tipo de documento.
     */
    public function label(): string
    {
        return match ($this) {
            self::ChileanRut => 'RUT (Chile)',
            self::ArgentineDni => 'DNI (Argentina)',
            self::PeruvianDni => 'DNI (Perú)',
            self::ColombianNit => 'NIT (Colombia)',
            self::MexicanRfc => 'RFC (México)',
            self::Generic => 'Identificación General / Pasaporte',
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
            self::PeruvianDni => 'PE',
            self::ColombianNit => 'CO',
            self::MexicanRfc => 'MX',
            self::Generic => 'OTHER',
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
            'PE', 'PER', 'PERU' => self::PeruvianDni,
            'CO', 'COL', 'COLOMBIA' => self::ColombianNit,
            'MX', 'MEX', 'MEXICO' => self::MexicanRfc,
            default => self::Generic,
        };
    }
}
