<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Tenant;
use Database\Factories\Concerns\GeneratesChileanRut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    use GeneratesChileanRut;

    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company(),
            'rut' => $this->fakeRut(),
            'billing_email' => fake()->companyEmail(),
            'billing_address' => fake()->streetAddress() . ', ' . fake()->city(),
            'contact_name' => fake()->name(),
            'contact_phone' => '+56 9 ' . fake()->numerify('########'),
            'cutoff_time' => '17:00:00',
            'cutoff_days_in_advance' => 1,
            'subsidy_percentage' => fake()->randomElement([80.00, 90.00, 100.00]),
            'is_active' => true,
            'settings' => [],
        ];
    }

    /**
     * Empresa con subsidio total (el comensal no paga copago).
     */
    public function fullySubsidized(): static
    {
        return $this->state(fn() => ['subsidy_percentage' => 100.00]);
    }

    /**
     * Empresa con copago parcial.
     */
    public function partialSubsidy(float $percentage = 80.00): static
    {
        return $this->state(fn() => ['subsidy_percentage' => $percentage]);
    }
}
