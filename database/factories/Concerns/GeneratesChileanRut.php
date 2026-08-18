<?php

namespace Database\Factories\Concerns;

/**
 * Genera RUTs chilenos con dígito verificador válido (algoritmo módulo 11),
 * para que los datos de prueba se vean y validen como RUTs reales.
 */
trait GeneratesChileanRut
{
    protected function fakeRut(int $min = 5_000_000, int $max = 27_000_000): string
    {
        $number = fake()->unique()->numberBetween($min, $max);

        return number_format($number, 0, '', '.') . '-' . $this->digitoVerificador($number);
    }

    protected function digitoVerificador(int $number): string
    {
        $sum = 0;
        $multiplier = 2;

        foreach (array_reverse(str_split((string) $number)) as $digit) {
            $sum += ((int) $digit) * $multiplier;
            $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
        }

        $remainder = 11 - ($sum % 11);

        return match ($remainder) {
            11 => '0',
            10 => 'K',
            default => (string) $remainder,
        };
    }
}
