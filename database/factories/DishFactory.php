<?php

namespace Database\Factories;

use App\Enums\DietaryTag;
use App\Models\Dish;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dish>
 */
class DishFactory extends Factory
{
    protected $model = Dish::class;

    protected array $fondos = [
        'Pollo al jugo con puré picante',
        'Salmón grillé con quinoa',
        'Lasaña de verduras',
        'Arroz con pollo y pimentones',
        'Cazuela de vacuno',
    ];

    protected array $ensaladas = [
        'Ensalada César con pollo',
        'Ensalada de garbanzos y palta',
        'Ensalada mediterránea',
    ];

    protected array $postres = ['Mousse de chocolate', 'Ensalada de frutas con yogurt'];

    protected array $hipocaloricos = [
        'Filete de pollo con vegetales al vapor',
        'Merluza al horno con ensalada verde',
    ];

    public function definition(): array
    {
        $category = fake()->randomElement(['Fondo', 'Ensalada', 'Postre', 'Hipocalórico']);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->nameForCategory($category),
            'description' => fake()->sentence(12),
            'category' => $category,
            'dietary_tags' => [DietaryTag::Standard->value],
            'allergens' => [],
            'calories_kcal' => fake()->numberBetween(280, 850),
            'raw_cost_clp' => fake()->numberBetween(1200, 3800),
            'image_url' => null,
            'is_active' => true,
        ];
    }

    protected function nameForCategory(string $category): string
    {
        $pool = match ($category) {
            'Fondo' => $this->fondos,
            'Ensalada' => $this->ensaladas,
            'Postre' => $this->postres,
            'Hipocalórico' => $this->hipocaloricos,
            default => $this->fondos,
        };

        return fake()->randomElement($pool);
    }

    public function category(string $category): static
    {
        return $this->state(fn() => [
            'category' => $category,
            'name' => $this->nameForCategory($category),
        ]);
    }

    public function vegan(): static
    {
        return $this->state(fn() => ['dietary_tags' => [DietaryTag::Vegan->value]]);
    }

    public function celiacFriendly(): static
    {
        return $this->state(fn() => ['dietary_tags' => [DietaryTag::Celiac->value]]);
    }

    public function withAllergens(array $allergens): static
    {
        return $this->state(fn() => ['allergens' => $allergens]);
    }
}
