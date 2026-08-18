<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 *
 * Nota: si se crea junto a una Company específica, encadenar ambos con
 * ->for($tenant)->for($company) para que tenant_id/company_id queden
 * consistentes entre sí.
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    protected array $communesRM = [
        'Las Condes',
        'Providencia',
        'Ñuñoa',
        'Santiago Centro',
        'Quilicura',
        'Vitacura',
        'Huechuraba',
        'San Miguel',
    ];

    public function definition(): array
    {
        $commune = fake()->randomElement($this->communesRM);

        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'name' => 'Sede ' . fake()->streetName(),
            'address' => fake()->streetAddress(),
            'commune' => $commune,
            'region' => 'Región Metropolitana',
            'contact_name' => fake()->name(),
            'contact_phone' => '+56 9 ' . fake()->numerify('########'),
            'delivery_time_start' => '12:30:00',
            'delivery_time_end' => '13:30:00',
            'delivery_notes' => fake()->optional()->sentence(),
            'latitude' => fake()->latitude(-33.65, -33.30),
            'longitude' => fake()->longitude(-70.80, -70.50),
            'is_active' => true,
        ];
    }
}
