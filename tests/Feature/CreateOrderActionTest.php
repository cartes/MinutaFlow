<?php

namespace Tests\Feature;

use App\Actions\Orders\CreateOrderAction;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Exceptions\SoldOutException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Company $company;
    private Branch $branch;
    private User $user;
    private Dish $dish;
    private Menu $menu;
    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Catering Express',
            'slug' => 'catering-express',
            'rut' => '76.123.456-7',
            'billing_email' => 'billing@catering.test',
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Tech Corp',
            'rut' => '77.987.654-3',
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Casa Central',
            'address' => 'Av. Providencia 1234',
            'commune' => 'Providencia',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@techcorp.test',
            'role' => UserRole::Employee,
            'password' => 'secret123',
        ]);

        $this->dish = Dish::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pollo con Arroz',
            'category' => 'Fondo',
        ]);

        $this->menu = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'menu_date' => '2026-08-20',
            'is_published' => true,
        ]);

        $this->menuItem = MenuItem::create([
            'menu_id' => $this->menu->id,
            'dish_id' => $this->dish->id,
            'option_label' => 'Opción 1 - Pollo Grillé',
            'max_quota' => 2,
            'price_extra_clp' => 1500,
            'is_available' => true,
        ]);
    }

    public function test_creates_order_successfully_when_quota_is_available(): void
    {
        $action = new CreateOrderAction();

        $order = $action->execute([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'menu_item_id' => $this->menuItem->id,
            'order_date' => '2026-08-20',
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'copay_amount_clp' => 1500,
            'status' => OrderStatus::Confirmed->value,
        ]);
        $this->assertEquals(1, $this->menuItem->fresh()->getConfirmedOrdersCount());
        $this->assertEquals(1, $this->menuItem->fresh()->remainingQuota());
    }

    public function test_prevents_order_creation_when_sold_out(): void
    {
        $action = new CreateOrderAction();

        // Crear 2 órdenes para llenar el max_quota = 2
        $user2 = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'María López',
            'email' => 'maria@techcorp.test',
            'role' => UserRole::Employee,
            'password' => 'secret123',
        ]);

        $action->execute([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'menu_item_id' => $this->menuItem->id,
            'order_date' => '2026-08-20',
        ]);

        $action->execute([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $user2->id,
            'menu_item_id' => $this->menuItem->id,
            'order_date' => '2026-08-20',
        ]);

        $this->assertTrue($this->menuItem->fresh()->isSoldOut());
        $this->assertEquals(0, $this->menuItem->fresh()->remainingQuota());

        // Intentar crear un tercer pedido debe lanzar SoldOutException
        $user3 = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Carlos Díaz',
            'email' => 'carlos@techcorp.test',
            'role' => UserRole::Employee,
            'password' => 'secret123',
        ]);

        $this->expectException(SoldOutException::class);

        $action->execute([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $user3->id,
            'menu_item_id' => $this->menuItem->id,
            'order_date' => '2026-08-20',
        ]);
    }

    public function test_cancelled_order_frees_quota(): void
    {
        $action = new CreateOrderAction();

        $order = $action->execute([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'menu_item_id' => $this->menuItem->id,
            'order_date' => '2026-08-20',
        ]);

        $this->assertEquals(1, $this->menuItem->fresh()->getConfirmedOrdersCount());

        $order->cancel('Cambio de planes');

        $this->assertEquals(0, $this->menuItem->fresh()->getConfirmedOrdersCount());
        $this->assertEquals(2, $this->menuItem->fresh()->remainingQuota());
        $this->assertFalse($this->menuItem->fresh()->isSoldOut());
    }

    public function test_fails_when_menu_item_is_not_available(): void
    {
        $this->menuItem->update(['is_available' => false]);

        $action = new CreateOrderAction();

        $this->expectException(SoldOutException::class);

        $action->execute([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'menu_item_id' => $this->menuItem->id,
            'order_date' => '2026-08-20',
        ]);
    }
}
