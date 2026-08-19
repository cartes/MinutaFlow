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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderManagementHttpTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Company $company;
    private Company $otherCompany;
    private Branch $branch;
    private User $tenantAdmin;
    private User $employee;
    private Dish $dishStandard;
    private Dish $dishAllergenic;
    private Menu $menuFuture;
    private MenuItem $itemStandard;
    private MenuItem $itemAllergenic;
    private MenuItem $itemLimitedQuota;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Catering MinutaFlow Pro',
            'slug' => 'catering-minutaflow-pro',
            'rut' => '76.777.888-9',
            'billing_email' => 'admin@minutapro.test',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Tech Solutions SpA',
            'rut' => '77.888.999-0',
            'cutoff_time' => '10:00',
            'cutoff_days_in_advance' => 0,
        ]);

        $this->otherCompany = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Fintech Corp SpA',
            'rut' => '77.999.000-1',
            'cutoff_time' => '10:00',
            'cutoff_days_in_advance' => 0,
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Oficina Las Condes',
            'address' => 'Apoquindo 4000',
            'commune' => 'Las Condes',
        ]);

        $this->tenantAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Catering',
            'email' => 'admin@minutapro.test',
            'password' => 'password123',
            'role' => UserRole::TenantAdmin,
            'is_active' => true,
        ]);

        $this->employee = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Camila Comensal',
            'email' => 'camila@techsolutions.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
            'allergies' => ['mani', 'mariscos'],
            'is_active' => true,
        ]);

        $this->dishStandard = Dish::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pechuga a la Plancha con Ensalada',
            'category' => 'Fondo',
            'allergens' => [],
            'calories_kcal' => 450,
        ]);

        $this->dishAllergenic = Dish::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pad Thai con Maní',
            'category' => 'Fondo',
            'allergens' => ['mani', 'soya'],
            'calories_kcal' => 650,
        ]);

        // Menú para dentro de 2 días (así evitamos problemas de horario de corte)
        $this->menuFuture = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => null, // Disponible para todas las empresas del tenant
            'title' => 'Menú Almuerzo Futuro',
            'menu_date' => now()->addDays(2)->toDateString(),
            'is_published' => true,
        ]);

        $this->itemStandard = MenuItem::create([
            'menu_id' => $this->menuFuture->id,
            'dish_id' => $this->dishStandard->id,
            'option_label' => 'Opción Ligera',
            'max_quota' => 50,
        ]);

        $this->itemAllergenic = MenuItem::create([
            'menu_id' => $this->menuFuture->id,
            'dish_id' => $this->dishAllergenic->id,
            'option_label' => 'Opción Tailandesa',
            'max_quota' => 50,
        ]);

        $this->itemLimitedQuota = MenuItem::create([
            'menu_id' => $this->menuFuture->id,
            'dish_id' => $this->dishStandard->id,
            'option_label' => 'Opción Cupo 1',
            'max_quota' => 1,
        ]);
    }

    public function test_employee_can_successfully_create_order_via_http(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
            'notes' => 'Sin aderezo por favor',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', OrderStatus::Confirmed->value);
        $response->assertJsonPath('notes', 'Sin aderezo por favor');
        $response->assertJsonPath('user_id', $this->employee->id);
        $response->assertJsonPath('company_id', $this->company->id);
        $response->assertJsonPath('branch_id', $this->branch->id);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->employee->id,
            'menu_item_id' => $this->itemStandard->id,
            'status' => OrderStatus::Confirmed->value,
        ]);
    }

    public function test_user_without_company_or_branch_cannot_create_order(): void
    {
        $floatingUser = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Usuario Flotante',
            'email' => 'flotante@test.com',
            'password' => 'password123',
            'role' => UserRole::Employee,
        ]);

        Sanctum::actingAs($floatingUser);

        $response = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
        ]);

        $response->assertForbidden();
        $response->assertJsonFragment([
            'message' => 'Tu cuenta no tiene empresa o sucursal asignada. Contacta a tu administrador.',
        ]);
    }

    public function test_cannot_order_from_unpublished_menu(): void
    {
        $this->menuFuture->update(['is_published' => false]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Este menú aún no está disponible para pedidos.',
        ]);
    }

    public function test_cannot_order_from_menu_exclusive_to_another_company(): void
    {
        $this->menuFuture->update(['company_id' => $this->otherCompany->id]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
        ]);

        $response->assertForbidden();
        $response->assertJsonFragment([
            'message' => 'Este menú no está disponible para tu empresa.',
        ]);
    }

    public function test_cannot_order_after_cutoff_time(): void
    {
        // Menú para hoy a las 10:00 cutoff. Simulamos que son las 11:00 AM.
        Carbon::setTestNow(now()->startOfDay()->setTime(11, 0));

        $menuToday = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => null,
            'title' => 'Menú de Hoy',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $itemToday = MenuItem::create([
            'menu_id' => $menuToday->id,
            'dish_id' => $this->dishStandard->id,
            'option_label' => 'Opción Hoy',
            'max_quota' => 20,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $itemToday->id,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('El plazo para pedir este menú venció', $response->json('message'));

        Carbon::setTestNow(); // Reset time
    }

    public function test_ordering_allergenic_dish_requires_explicit_confirmation(): void
    {
        Sanctum::actingAs($this->employee);

        // Sin accept_allergen_risk -> debe advertir
        $response = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemAllergenic->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('requires_confirmation', true);
        $response->assertJsonFragment(['allergens' => ['mani']]);

        // Con accept_allergen_risk = true -> crea el pedido
        $confirmedResponse = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemAllergenic->id,
            'accept_allergen_risk' => true,
        ]);

        $confirmedResponse->assertCreated();
    }

    public function test_cannot_order_when_item_quota_is_exhausted(): void
    {
        // Creamos una orden previa que llene el cupo
        Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => User::factory()->create(['tenant_id' => $this->tenant->id, 'company_id' => $this->company->id])->id,
            'menu_id' => $this->menuFuture->id,
            'menu_item_id' => $this->itemLimitedQuota->id,
            'order_date' => $this->menuFuture->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'full-quota-order'),
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemLimitedQuota->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Esta opción ya no tiene cupos disponibles.',
        ]);
    }

    public function test_cannot_create_duplicate_active_order_for_same_day(): void
    {
        Sanctum::actingAs($this->employee);

        // Primer pedido
        $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
        ])->assertCreated();

        // Segundo intento para el mismo día
        $response = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Ya tienes un pedido activo para este día. Cancélalo antes de crear uno nuevo.',
        ]);
    }

    public function test_replaces_previously_cancelled_order_on_same_day(): void
    {
        Sanctum::actingAs($this->employee);

        // Creamos y cancelamos primer pedido
        $createRes = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
        ]);
        $createRes->assertCreated();
        $orderId = $createRes->json('id');

        $this->postJson("/api/v1/orders/{$orderId}/cancel", [
            'reason' => 'Cambio de parecer',
        ])->assertOk();

        // Nuevo pedido para el mismo día
        $newRes = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
            'notes' => 'Pedido nuevo tras cancelación',
        ]);

        $newRes->assertCreated();
        $newRes->assertJsonPath('status', OrderStatus::Confirmed->value);
        $newRes->assertJsonPath('notes', 'Pedido nuevo tras cancelación');
    }

    public function test_employee_can_cancel_order_before_cutoff(): void
    {
        Sanctum::actingAs($this->employee);

        $createRes = $this->postJson('/api/v1/orders', [
            'menu_item_id' => $this->itemStandard->id,
        ]);
        $orderId = $createRes->json('id');

        $cancelRes = $this->postJson("/api/v1/orders/{$orderId}/cancel", [
            'reason' => 'Reunión fuera de la oficina',
        ]);

        $cancelRes->assertOk();
        $cancelRes->assertJsonPath('status', OrderStatus::Cancelled->value);
        $cancelRes->assertJsonPath('cancellation_reason', 'Reunión fuera de la oficina');
    }

    public function test_employee_cannot_cancel_order_after_cutoff(): void
    {
        // Pedido de hoy a las 11:00 AM (cutoff 10:00 AM)
        Carbon::setTestNow(now()->startOfDay()->setTime(11, 0));

        $menuToday = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => null,
            'title' => 'Menú Hoy',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $itemToday = MenuItem::create([
            'menu_id' => $menuToday->id,
            'dish_id' => $this->dishStandard->id,
            'option_label' => 'Opción Hoy',
            'max_quota' => 20,
        ]);

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->employee->id,
            'menu_id' => $menuToday->id,
            'menu_item_id' => $itemToday->id,
            'order_date' => $menuToday->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'order-today-diego'),
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [
            'reason' => 'Quiero cancelar tarde',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('El plazo para cancelar venció', $response->json('message'));

        Carbon::setTestNow();
    }

    public function test_tenant_admin_can_cancel_orders_even_after_cutoff(): void
    {
        Carbon::setTestNow(now()->startOfDay()->setTime(11, 0));

        $menuToday = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => null,
            'title' => 'Menú Hoy',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $itemToday = MenuItem::create([
            'menu_id' => $menuToday->id,
            'dish_id' => $this->dishStandard->id,
            'option_label' => 'Opción Hoy',
            'max_quota' => 20,
        ]);

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->employee->id,
            'menu_id' => $menuToday->id,
            'menu_item_id' => $itemToday->id,
            'order_date' => $menuToday->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'order-today-admin-cancel'),
        ]);

        Sanctum::actingAs($this->tenantAdmin);

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [
            'reason' => 'Falla de insumos en cocina',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', OrderStatus::Cancelled->value);
        $response->assertJsonPath('cancellation_reason', 'Falla de insumos en cocina');

        Carbon::setTestNow();
    }
}
