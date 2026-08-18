<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => null, // null = menú común para todos los clientes del tenant
            'title' => 'Menú del día',
            'menu_date' => fake()->dateTimeBetween('now', '+2 weeks')->format('Y-m-d'),
            'is_published' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(fn() => ['is_published' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn() => ['is_published' => false]);
    }

    public function forDate(\DateTimeInterface|string $date): static
    {
        return $this->state(fn() => [
            'menu_date' => \Carbon\Carbon::parse($date)->format('Y-m-d'),
        ]);
    }
}
