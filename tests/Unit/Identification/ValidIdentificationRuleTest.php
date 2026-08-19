<?php

namespace Tests\Unit\Identification;

use App\Enums\IdentificationType;
use App\Models\User;
use App\Rules\ValidIdentification;
use App\Services\Identification\IdentificationManager;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidIdentificationRuleTest extends TestCase
{
    public function test_identification_manager_resolves_chile_and_argentina(): void
    {
        /** @var IdentificationManager $manager */
        $manager = app(IdentificationManager::class);

        $clDriver = $manager->driver('CL');
        $this->assertSame(IdentificationType::ChileanRut, $clDriver->type());

        $arDriver = $manager->driver('AR');
        $this->assertSame(IdentificationType::ArgentineDni, $arDriver->type());
    }

    public function test_valid_identification_rule_passes_with_valid_rut(): void
    {
        $validator = Validator::make(
            ['rut' => '12.345.678-5'],
            ['rut' => [new ValidIdentification('CL')]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_valid_identification_rule_fails_with_invalid_rut(): void
    {
        $validator = Validator::make(
            ['rut' => '12.345.678-9'],
            ['rut' => [new ValidIdentification('CL')]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'El RUT chileno ingresado no es válido.',
            $validator->errors()->first('rut')
        );
    }

    public function test_valid_identification_supports_argentina_dni(): void
    {
        $validatorValid = Validator::make(
            ['dni' => '35.123.456'],
            ['dni' => [ValidIdentification::argentina()]]
        );
        $this->assertTrue($validatorValid->passes());

        $validatorInvalid = Validator::make(
            ['dni' => '123'], // Demasiado corto
            ['dni' => [ValidIdentification::argentina()]]
        );
        $this->assertTrue($validatorInvalid->fails());
        $this->assertSame(
            'El DNI argentino ingresado no es válido.',
            $validatorInvalid->errors()->first('dni')
        );
    }

    public function test_user_model_accessors_and_helpers(): void
    {
        $user = new User([
            'name' => 'Juan Pérez',
            'email' => 'juan@test.com',
            'rut' => '12345678-5',
        ]);

        $this->assertSame('12.345.678-5', $user->formatted_rut);
        $this->assertSame('12345678-5', $user->clean_rut);
        $this->assertTrue($user->hasValidRut());

        $invalidUser = new User([
            'rut' => '11111111-9',
        ]);
        $this->assertFalse($invalidUser->hasValidRut());
    }
}
