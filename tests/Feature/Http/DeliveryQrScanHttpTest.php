<?php

namespace Tests\Feature\Http;

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

class DeliveryQrScanHttpTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private Company $companyA;
    private Company $companyB;
    private Branch $branchA;
    private User $tenantAdminA;
    private User $kitchenOperatorA;
    private User $companyAdminA;
    private User $employeeA;
    private Dish $dishA;
    private Menu $menuToday;
    private MenuItem $menuItemA;
    private Order $orderToday;
    private string $validQrHash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Catering Central',
            'slug' => 'catering-central',
            'rut' => '76.333.333-3',
            'billing_email' => 'admin@central.test',
            'is_active' => true,
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Catering Rival',
            'slug' => 'catering-rival',
            'rut' => '76.444.444-4',
            'billing_email' => 'admin@rival.test',
            'is_active' => true,
        ]);

        $this->companyA = Company::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Empresa Cliente A',
            'rut' => '77.555.555-5',
        ]);

        $this->companyB = Company::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Empresa Cliente B',
            'rut' => '77.666.666-6',
        ]);

        $this->branchA = Branch::create([
            'tenant_id' => $this->tenantA->id,
            'company_id' => $this->companyA->id,
            'name' => 'Sucursal Santiago Centro',
            'address' => 'Morandé 50',
            'commune' => 'Santiago',
        ]);

        $this->tenantAdminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin Central',
            'email' => 'admin@central.test',
            'password' => 'password123',
            'role' => UserRole::TenantAdmin,
            'is_active' => true,
        ]);

        $this->kitchenOperatorA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Chef Rodrigo',
            'email' => 'chef@central.test',
            'password' => 'password123',
            'role' => UserRole::KitchenOperator,
            'is_active' => true,
        ]);

        $this->companyAdminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'RRHH Empresa A',
            'email' => 'rrhh@empresaa.test',
            'password' => 'password123',
            'role' => UserRole::CompanyAdmin,
            'is_active' => true,
        ]);

        $this->employeeA = User::create([
            'tenant_id' => $this->tenantA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Diego Comensal',
            'email' => 'diego@empresaa.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $this->dishA = Dish::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Pollo Arvejado con Arroz',
            'category' => 'Fondo',
            'calories_kcal' => 520,
        ]);

        $this->menuToday = Menu::create([
            'tenant_id' => $this->tenantA->id,
            'title' => 'Menú de Hoy',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $this->menuItemA = MenuItem::create([
            'menu_id' => $this->menuToday->id,
            'dish_id' => $this->dishA->id,
            'option_label' => 'Opción Pollo',
            'max_quota' => 50,
        ]);

        $this->validQrHash = hash('sha256', 'order-diego-secret-token');

        $this->orderToday = Order::create([
            'tenant_id' => $this->tenantA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->employeeA->id,
            'menu_id' => $this->menuToday->id,
            'menu_item_id' => $this->menuItemA->id,
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => $this->validQrHash,
        ]);
    }

    public function test_kitchen_operator_can_successfully_scan_and_deliver_order(): void
    {
        Sanctum::actingAs($this->kitchenOperatorA);

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => $this->validQrHash,
        ]);

        $response->assertOk();
        $response->assertJsonPath('order.status', OrderStatus::Delivered->value);
        $response->assertJsonPath('order.id', $this->orderToday->id);
        $response->assertJsonFragment([
            'message' => "Entrega registrada: Diego Comensal — Pollo Arvejado con Arroz.",
        ]);

        $this->orderToday->refresh();
        $this->assertEquals(OrderStatus::Delivered, $this->orderToday->status);
        $this->assertNotNull($this->orderToday->delivered_at);
    }

    public function test_tenant_admin_can_also_scan_and_deliver_order(): void
    {
        Sanctum::actingAs($this->tenantAdminA);

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => $this->validQrHash,
        ]);

        $response->assertOk();
        $response->assertJsonPath('order.status', OrderStatus::Delivered->value);
    }

    public function test_employee_and_company_admin_cannot_scan_delivery_qr(): void
    {
        foreach ([$this->employeeA, $this->companyAdminA] as $user) {
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/delivery/scan', [
                'qr_code_hash' => $this->validQrHash,
            ]);

            $response->assertForbidden();
        }
    }

    public function test_unauthenticated_user_cannot_scan_delivery_qr(): void
    {
        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => $this->validQrHash,
        ]);

        $response->assertUnauthorized();
    }

    public function test_scan_fails_when_order_is_already_delivered(): void
    {
        $this->orderToday->markAsDelivered();

        Sanctum::actingAs($this->kitchenOperatorA);

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => $this->validQrHash,
        ]);

        $response->assertStatus(409);
        $response->assertJsonFragment([
            'message' => "Este pedido ya fue entregado el {$this->orderToday->delivered_at->format('d-m-Y H:i')}.",
        ]);
    }

    public function test_scan_fails_when_order_is_cancelled(): void
    {
        $this->orderToday->cancel('Cancelado por usuario');

        Sanctum::actingAs($this->kitchenOperatorA);

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => $this->validQrHash,
        ]);

        $response->assertStatus(409);
        $response->assertJsonFragment([
            'message' => 'Este pedido fue cancelado y no debe entregarse.',
        ]);
    }

    public function test_scan_fails_when_order_date_is_not_today(): void
    {
        $pastDate = now()->subDays(1)->toDateString();
        $this->orderToday->update(['order_date' => $pastDate]);

        Sanctum::actingAs($this->kitchenOperatorA);

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => $this->validQrHash,
        ]);

        $response->assertStatus(409);
        $response->assertJsonFragment([
            'message' => "Este pedido corresponde al {$this->orderToday->fresh()->order_date->format('d-m-Y')}, no a hoy.",
        ]);
    }

    public function test_scan_returns_404_for_unknown_qr_code_hash(): void
    {
        Sanctum::actingAs($this->kitchenOperatorA);

        $randomHash = hash('sha256', 'completely-nonexistent-qr');

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => $randomHash,
        ]);

        $response->assertNotFound();
        $response->assertJson(['message' => 'Código QR no válido o pedido inexistente.']);
    }

    public function test_scan_returns_404_when_scanning_qr_of_another_tenant(): void
    {
        // Setup completo para pedido perteneciente a Tenant B
        $branchB = Branch::create([
            'tenant_id' => $this->tenantB->id,
            'company_id' => $this->companyB->id,
            'name' => 'Sucursal Rival',
            'address' => 'Apoquindo 100',
            'commune' => 'Las Condes',
        ]);

        $userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $branchB->id,
            'name' => 'Usuario B',
            'email' => 'userb@rival.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
        ]);

        $dishB = Dish::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Plato B',
            'category' => 'Fondo',
        ]);

        $menuB = Menu::create([
            'tenant_id' => $this->tenantB->id,
            'title' => 'Menú B',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $itemB = MenuItem::create([
            'menu_id' => $menuB->id,
            'dish_id' => $dishB->id,
            'option_label' => 'Opción B',
            'max_quota' => 10,
        ]);

        $orderB = Order::create([
            'tenant_id' => $this->tenantB->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $branchB->id,
            'user_id' => $userB->id,
            'menu_id' => $menuB->id,
            'menu_item_id' => $itemB->id,
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'tenant-b-qr-secret'),
        ]);

        // Operador de cocina de Tenant A intenta escanearlo
        Sanctum::actingAs($this->kitchenOperatorA);

        $response = $this->postJson('/api/v1/delivery/scan', [
            'qr_code_hash' => $orderB->qr_code_hash,
        ]);

        $response->assertNotFound();
        $response->assertJson(['message' => 'Código QR no válido o pedido inexistente.']);
    }
}
