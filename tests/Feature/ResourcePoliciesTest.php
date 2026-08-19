<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResourcePoliciesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Company $company;
    private Branch $branch;
    private User $tenantAdmin;
    private User $kitchenOperator;
    private User $employee;
    private Dish $dish;
    private Menu $menu;
    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Catering Gourmet',
            'slug' => 'catering-gourmet',
            'rut' => '76.333.333-3',
            'billing_email' => 'gourmet@test.com',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Empresa Cliente',
            'rut' => '77.444.444-4',
            'cutoff_time' => '10:00',
            'cutoff_days_in_advance' => 0,
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Sucursal Central',
            'address' => 'Alameda 100',
            'commune' => 'Santiago',
        ]);

        $this->tenantAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Catering',
            'email' => 'admin@gourmet.test',
            'password' => 'password123',
            'role' => UserRole::TenantAdmin,
        ]);

        $this->kitchenOperator = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Chef Cocina',
            'email' => 'chef@gourmet.test',
            'password' => 'password123',
            'role' => UserRole::KitchenOperator,
        ]);

        $this->employee = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Juan Comensal',
            'email' => 'juan@cliente.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
        ]);

        $this->dish = Dish::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Salmón Grillé',
            'category' => 'Fondo',
        ]);

        $this->menu = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => null,
            'title' => 'Menú Ejecutivo',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $this->menuItem = MenuItem::create([
            'menu_id' => $this->menu->id,
            'dish_id' => $this->dish->id,
            'option_label' => 'Opción A',
            'max_quota' => 50,
        ]);
    }

    public function test_employee_cannot_create_dishes(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/dishes', [
            'name' => 'Plato No Permitido',
            'category' => 'Fondo',
        ]);

        $response->assertForbidden();
    }

    public function test_employee_cannot_view_dish_catalog(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->getJson('/api/v1/dishes');
        $response->assertForbidden();

        $showResponse = $this->getJson("/api/v1/dishes/{$this->dish->id}");
        $showResponse->assertForbidden();
    }

    public function test_kitchen_operator_can_view_dishes_but_cannot_create_them(): void
    {
        Sanctum::actingAs($this->kitchenOperator);

        // Puede ver catálogo
        $indexResponse = $this->getJson('/api/v1/dishes');
        $indexResponse->assertOk();

        // No puede crear platos
        $createResponse = $this->postJson('/api/v1/dishes', [
            'name' => 'Plato Cocinero',
            'category' => 'Fondo',
        ]);
        $createResponse->assertForbidden();
    }

    public function test_employee_cannot_create_or_modify_menus(): void
    {
        Sanctum::actingAs($this->employee);

        $createResponse = $this->postJson('/api/v1/menus', [
            'title' => 'Menú Empleado',
            'menu_date' => now()->addDays(3)->toDateString(),
        ]);
        $createResponse->assertForbidden();

        $updateResponse = $this->putJson("/api/v1/menus/{$this->menu->id}", [
            'title' => 'Menú Modificado',
        ]);
        $updateResponse->assertForbidden();

        $publishResponse = $this->postJson("/api/v1/menus/{$this->menu->id}/publish");
        $publishResponse->assertForbidden();
    }

    public function test_employee_cannot_scan_delivery_qr(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => hash('sha256', 'dummy-test-hash'),
        ]);

        $response->assertForbidden();
    }

    public function test_kitchen_operator_can_scan_delivery_qr(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->employee->id,
            'menu_id' => $this->menu->id,
            'menu_item_id' => $this->menuItem->id,
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'order-unique-qr-123'),
        ]);

        Sanctum::actingAs($this->kitchenOperator);

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => hash('sha256', 'order-unique-qr-123'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('order.status', OrderStatus::Delivered->value);
    }
}
