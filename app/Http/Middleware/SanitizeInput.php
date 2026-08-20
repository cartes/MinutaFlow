<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Intercepta y limpia recursivamente la entrada para prevenir null-byte injection y caracteres de control maliciosos.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if (!empty($input)) {
            $cleaned = $this->cleanArray($input);
            $request->merge($cleaned);
        }

        return $next($request);
    }

    /**
     * Limpia un arreglo de inputs de forma recursiva.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function cleanArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->cleanArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->cleanString($value);
            }
        }

        return $data;
    }

    /**
     * Elimina bytes nulos y caracteres de control no permitidos.
     */
    protected function cleanString(string $value): string
    {
        // 1. Eliminar Null Bytes (\0)
        $value = str_replace(["\0", "\x00"], '', $value);

        // 2. Eliminar caracteres de control invisibles excepto saltos de línea (\r, \n) y tabulación (\t)
        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    }
}
