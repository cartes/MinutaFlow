<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'dish_id' => Dish::factory(),
            'option_label' => 'Opción A (Proteica)',
            'max_quota' => fake()->numberBetween(30, 120),
            'price_extra_clp' => 0,
            'is_available' => true,
            'sort_order' => 0,
        ];
    }

    public function label(string $label, int $sortOrder = 0): static
    {
        return $this->state(fn() => [
            'option_label' => $label,
            'sort_order' => $sortOrder,
        ]);
    }

    public function withExtraCharge(int $priceExtraClp): static
    {
        return $this->state(fn() => ['price_extra_clp' => $priceExtraClp]);
    }

    public function unlimitedQuota(): static
    {
        return $this->state(fn() => ['max_quota' => null]);
    }

    public function soldOut(): static
    {
        return $this->state(fn() => ['is_available' => false]);
    }
}
