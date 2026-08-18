<?php

namespace Database\Factories;

use App\Models\Tenant;
use Database\Factories\Concerns\GeneratesChileanRut;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    use GeneratesChileanRut;

    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company() . ' Catering';

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(100, 999),
            'rut' => $this->fakeRut(),
            'billing_email' => fake()->companyEmail(),
            'phone' => '+56 9 ' . fake()->numerify('########'),
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
            'is_active' => true,
            'settings' => [],
        ];
    }
}
