<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 *
 * qr_code_hash no se define aquí a propósito: Order::booted() lo genera
 * automáticamente en el evento "creating" si llega vacío.
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'menu_id' => Menu::factory(),
            'menu_item_id' => MenuItem::factory(),
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::Confirmed,
            'copay_amount_clp' => 0,
            'notes' => fake()->optional(0.2)->sentence(),
            'delivered_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    public function forDate(\DateTimeInterface|string $date): static
    {
        return $this->state(fn() => [
            'order_date' => \Carbon\Carbon::parse($date)->toDateString(),
        ]);
    }

    public function withCopay(int $amountClp): static
    {
        return $this->state(fn() => ['copay_amount_clp' => $amountClp]);
    }

    public function delivered(): static
    {
        return $this->state(fn() => [
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(string $reason = 'Cancelado por el comensal antes del corte'): static
    {
        return $this->state(fn() => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn() => ['status' => OrderStatus::NoShow]);
    }
}
