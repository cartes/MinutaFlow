<?php

namespace Database\Factories;

use App\Enums\DietaryTag;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 *
 * Al usarse para un comensal real, encadenar ->for($tenant)->for($company)
 * y setear branch_id explícitamente para mantener la jerarquía consistente.
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'branch_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'rut' => fake()->optional(0.8)->numerify('#########'),
            'phone' => '+56 9 ' . fake()->numerify('########'),
            'role' => UserRole::Employee,
            'dietary_preferences' => [DietaryTag::Standard->value],
            'allergies' => [],
            'email_verified_at' => now(),
            'password' => 'password', // el cast 'hashed' del modelo lo encripta al guardar
            'is_active' => true,
            'remember_token' => \Illuminate\Support\Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn() => ['email_verified_at' => null]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn() => [
            'tenant_id' => null,
            'company_id' => null,
            'branch_id' => null,
            'role' => UserRole::SuperAdmin,
            'dietary_preferences' => null,
            'allergies' => null,
        ]);
    }

    public function tenantAdmin(): static
    {
        return $this->state(fn() => [
            'company_id' => null,
            'branch_id' => null,
            'role' => UserRole::TenantAdmin,
            'dietary_preferences' => null,
            'allergies' => null,
        ]);
    }

    public function kitchenOperator(): static
    {
        return $this->state(fn() => [
            'company_id' => null,
            'branch_id' => null,
            'role' => UserRole::KitchenOperator,
            'dietary_preferences' => null,
            'allergies' => null,
        ]);
    }

    public function companyAdmin(): static
    {
        return $this->state(fn() => [
            'role' => UserRole::CompanyAdmin,
            'dietary_preferences' => null,
            'allergies' => null,
        ]);
    }

    public function employee(): static
    {
        return $this->state(fn() => ['role' => UserRole::Employee]);
    }

    public function vegan(): static
    {
        return $this->state(fn() => ['dietary_preferences' => [DietaryTag::Vegan->value]]);
    }

    public function celiac(): static
    {
        return $this->state(fn() => ['dietary_preferences' => [DietaryTag::Celiac->value]]);
    }

    public function withAllergies(array $allergens): static
    {
        return $this->state(fn() => ['allergies' => $allergens]);
    }
}
