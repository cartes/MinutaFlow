<?php

namespace Tests\Unit\Identification;

use App\Services\Identification\Drivers\ChileanRutValidator;
use PHPUnit\Framework\TestCase;

class ChileanRutValidatorTest extends TestCase
{
    private ChileanRutValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ChileanRutValidator();
    }

    public function test_validates_correct_chilean_ruts_with_digits(): void
    {
        $validRuts = [
            '11.111.111-1',
            '11111111-1',
            '111111111',
            '12.345.678-5',
            '12345678-5',
            '123456785',
            '76.123.456-0',
            '76123456-0',
            '10.000.027-K',
            '10000027-k',
            '6-K',
            '23-k',
            '7.612.345-4',
            '1-9',
        ];

        foreach ($validRuts as $rut) {
            $this->assertTrue(
                $this->validator->validate($rut),
                "Falló la validación para el RUT válido: {$rut}"
            );
        }
    }

    public function test_rejects_invalid_chilean_ruts(): void
    {
        $invalidRuts = [
            '11.111.111-2', // DV incorrecto
            '12.345.678-0', // DV incorrecto
            '76.123.456-8', // DV incorrecto
            '10.000.027-1', // DV incorrecto (debe ser K)
            '12345678901',  // Demasiado largo
            '0-0',          // Cuerpo cero
            'abc-1',        // Letras en cuerpo
            '12.345.67A-1', // Caracteres inválidos
            '',
            '   ',
        ];

        foreach ($invalidRuts as $rut) {
            $this->assertFalse(
                $this->validator->validate($rut),
                "Se esperaba que el RUT fuera inválido: {$rut}"
            );
        }
    }

    public function test_cleans_rut_properly(): void
    {
        $this->assertSame('12345678-5', $this->validator->clean('12.345.678-5'));
        $this->assertSame('10000027-K', $this->validator->clean('10.000.027-k'));
        $this->assertSame('12345678-5', $this->validator->clean('  123456785  '));
    }

    public function test_formats_rut_with_dots_and_dash(): void
    {
        $this->assertSame('12.345.678-5', $this->validator->format('12345678-5'));
        $this->assertSame('10.000.027-K', $this->validator->format('10000027k'));
        $this->assertSame('6-K', $this->validator->format('6k'));
    }
}
