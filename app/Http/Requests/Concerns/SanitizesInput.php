<?php

namespace App\Http\Requests\Concerns;

use App\Services\Identification\IdentificationManager;

trait SanitizesInput
{
    /**
     * Campos que no deben ser modificados por strip_tags (ej: contraseñas).
     *
     * @var list<string>
     */
    protected array $dontStripTags = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    /**
     * Campos que deben ser preservados exactamente (sin trim ni alteración de mayúsculas).
     *
     * @var list<string>
     */
    protected array $rawFields = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    /**
     * Hook de Laravel FormRequest antes de ejecutar la validación.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeInputs();
    }

    /**
     * Sanitiza los datos de entrada del request de manera recursiva y declarativa.
     */
    public function sanitizeInputs(): void
    {
        $input = $this->all();

        $sanitized = $this->sanitizeArray($input);

        // Aplicar sanitizadores personalizados definidos en el FormRequest
        if (method_exists($this, 'sanitizers')) {
            $sanitized = $this->applyCustomSanitizers($sanitized, $this->sanitizers());
        } elseif (property_exists($this, 'sanitizers') && is_array($this->sanitizers)) {
            $sanitized = $this->applyCustomSanitizers($sanitized, $this->sanitizers);
        }

        $this->replace($sanitized);
    }

    /**
     * Sanitiza recursivamente un arreglo de inputs.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($key, $value);
            }
        }

        return $data;
    }

    /**
     * Sanitiza una cadena individual previniendo XSS, null-bytes e inyección de scripts.
     */
    protected function sanitizeString(string $key, string $value): string
    {
        // 1. Eliminar Null Bytes (\0) siempre, incluso en contraseñas para evitar truncamiento
        $value = str_replace(chr(0), '', $value);

        // 2. Si es un campo raw (como contraseña), no aplicar trim ni strip_tags
        if ($this->isRawField($key)) {
            return $value;
        }

        // 3. Eliminar etiquetas HTML y PHP para prevenir XSS y ataques de script
        if (!$this->shouldPreserveTags($key)) {
            $value = strip_tags($value);
        }

        // 4. Normalizar espacios en blanco (trim)
        $value = trim($value);

        // 5. Normalizaciones automáticas por nombre de campo
        if (in_array(strtolower($key), ['email', 'billing_email'], true)) {
            $value = strtolower(filter_var($value, FILTER_SANITIZE_EMAIL) ?: $value);
        }

        if (in_array(strtolower($key), ['rut', 'tax_id', 'identification_number'], true)) {
            $value = $this->cleanIdentificationValue($value);
        }

        return $value;
    }

    /**
     * Limpia un valor de RUT / documento de identidad.
     */
    protected function cleanIdentificationValue(string $value): string
    {
        $clean = strtoupper(trim($value));
        // Remueve caracteres de control o scripts maliciosos preservando alfanuméricos, puntos y guiones
        return (string) preg_replace('/[^A-Za-z0-9._-]/', '', $clean);
    }

    /**
     * Determina si el campo no debe sufrir alteración de formato.
     */
    protected function isRawField(string $key): bool
    {
        return in_array($key, $this->rawFields, true);
    }

    /**
     * Determina si se deben preservar etiquetas en el campo.
     */
    protected function shouldPreserveTags(string $key): bool
    {
        return in_array($key, $this->dontStripTags, true);
    }

    /**
     * Aplica sanitizadores declarativos específicos definidos en el request.
     *
     * @param array<string, mixed> $data
     * @param array<string, string|callable> $sanitizers
     * @return array<string, mixed>
     */
    protected function applyCustomSanitizers(array $data, array $sanitizers): array
    {
        foreach ($sanitizers as $field => $rules) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if (is_callable($rules)) {
                $data[$field] = $rules($data[$field]);
                continue;
            }

            if (is_string($rules)) {
                $ruleList = explode('|', $rules);
                foreach ($ruleList as $rule) {
                    $data[$field] = $this->executeSanitizerRule($rule, $data[$field]);
                }
            }
        }

        return $data;
    }

    /**
     * Ejecuta una regla de sanitización individual.
     */
    protected function executeSanitizerRule(string $rule, mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return match ($rule) {
            'trim' => trim($value),
            'lowercase' => mb_strtolower($value, 'UTF-8'),
            'uppercase' => mb_strtoupper($value, 'UTF-8'),
            'strip_tags' => strip_tags($value),
            'email' => strtolower(filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: trim($value)),
            'digits' => preg_replace('/\D/', '', $value) ?? '',
            'alphanumeric' => preg_replace('/[^\p{L}\p{N}]/u', '', $value) ?? '',
            'clean_rut' => app(IdentificationManager::class)->clean($value, 'CL'),
            default => $value,
        };
    }
}
