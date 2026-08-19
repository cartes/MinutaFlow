<?php

namespace App\Services\Identification\Contracts;

use App\Enums\IdentificationType;

interface IdentificationValidatorInterface
{
    /**
     * Retorna el tipo de identificación que gestiona este validador.
     */
    public function type(): IdentificationType;

    /**
     * Verifica si el valor de la identificación es válido (formato y dígito verificador / checksum).
     */
    public function validate(string $value): bool;

    /**
     * Limpia el valor quitando caracteres cosméticos y estandarizando mayúsculas (ej: 12345678-K).
     */
    public function clean(string $value): string;

    /**
     * Formatea el número de identificación según el estándar visual del país (ej: 12.345.678-K).
     */
    public function format(string $value): string;

    /**
     * Mensaje de error a desplegar cuando la validación falla.
     */
    public function getErrorMessage(): string;
}
