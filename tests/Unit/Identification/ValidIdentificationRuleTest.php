<?php

namespace Tests\Unit\Identification;

use App\Enums\IdentificationType;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\ValidIdentification;
use App\Services\Identification\IdentificationManager;
use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidIdentificationRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_identification_manager_resolves_all_supported_drivers(): void
    {
        /** @var IdentificationManager $manager */
        $manager = app(IdentificationManager::class);

        $this->assertSame(IdentificationType::ChileanRut, $manager->driver('CL')->type());
        $this->assertSame(IdentificationType::ArgentineDni, $manager->driver('AR')->type());
        $this->assertSame(IdentificationType::PeruvianDni, $manager->driver('PE')->type());
        $this->assertSame(IdentificationType::ColombianNit, $manager->driver('CO')->type());
        $this->assertSame(IdentificationType::Generic, $manager->driver('OTHER')->type());
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
    }

    public function test_valid_identification_supports_peru_dni(): void
    {
        $validatorValid = Validator::make(
            ['dni' => '12345678'],
            ['dni' => [ValidIdentification::peru()]]
        );
        $this->assertTrue($validatorValid->passes());

        $validatorInvalid = Validator::make(
            ['dni' => '1234'],
            ['dni' => [ValidIdentification::peru()]]
        );
        $this->assertTrue($validatorInvalid->fails());
    }

    public function test_valid_identification_supports_colombia_nit(): void
    {
        // 900.123.456 con DV calculado módulo 11 = 8
        $validNit = '900.123.456-8';

        $validatorValid = Validator::make(
            ['nit' => $validNit],
            ['nit' => [ValidIdentification::colombia()]]
        );
        $this->assertTrue($validatorValid->passes());

        $validatorInvalid = Validator::make(
            ['nit' => '900.123.456-9'], // DV erróneo
            ['nit' => [ValidIdentification::colombia()]]
        );
        $this->assertTrue($validatorInvalid->fails());
    }

    public function test_valid_identification_supports_generic_passport(): void
    {
        $validatorValid = Validator::make(
            ['id' => 'PASSPORT-123456'],
            ['id' => [ValidIdentification::generic()]]
        );
        $this->assertTrue($validatorValid->passes());

        $validatorInvalid = Validator::make(
            ['id' => 'abc<script>'],
            ['id' => [ValidIdentification::generic()]]
        );
        $this->assertTrue($validatorInvalid->fails());
    }

    public function test_valid_identification_resolves_context_dynamically_from_company_and_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'International Catering',
            'slug' => 'international-catering',
            'rut' => '76.111.222-3',
            'billing_email' => 'catering@intl.test',
            'settings' => ['country' => 'AR'],
        ]);

        $company = Company::create([
            'tenant_id' => $tenant->id,
            'name' => 'Peruvian Branch SAC',
            'rut' => '12345678',
            'settings' => ['country' => 'PE'],
        ]);

        // Regla usando la empresa (debe validar según Perú DNI - 8 dígitos)
        $validatorPe = Validator::make(
            ['document' => '87654321'],
            ['document' => [ValidIdentification::forCurrentContext($company)]]
        );
        $this->assertTrue($validatorPe->passes());

        // Regla usando el tenant (debe validar según Argentina DNI - 7 u 8 dígitos)
        $validatorAr = Validator::make(
            ['document' => '32.456.789'],
            ['document' => [ValidIdentification::forCurrentContext(null, $tenant)]]
        );
        $this->assertTrue($validatorAr->passes());
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
