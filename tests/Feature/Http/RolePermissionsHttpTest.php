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

class RolePermissionsHttpTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Company $companyA;
    private Company $companyB;
    private Branch $branchA;
    private Branch $branchB;
    private User $tenantAdmin;
    private User $kitchenOperator;
    private User $companyAdmin;
    private User $employeeA;
    private User $employeeB;
    private Dish $dish;
    private Menu $publishedMenu;
    private Menu $draftMenu;
    private Menu $exclusiveMenuB;
    private MenuItem $menuItemA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Catering Gourmet Pro',
            'slug' => 'catering-gourmet-pro',
            'rut' => '76.555.666-7',
            'billing_email' => 'admin@gourmetpro.test',
            'is_active' => true,
        ]);

        $this->companyA = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Empresa Alfa',
            'rut' => '77.111.111-1',
            'cutoff_time' => '10:00',
            'cutoff_days_in_advance' => 0,
        ]);

        $this->companyB = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Empresa Beta',
            'rut' => '77.222.222-2',
            'cutoff_time' => '10:00',
            'cutoff_days_in_advance' => 0,
        ]);

        $this->branchA = Branch::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyA->id,
            'name' => 'Sucursal Alfa 1',
            'address' => 'Av. Siempre Viva 123',
            'commune' => 'Santiago',
        ]);

        $this->branchB = Branch::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyB->id,
            'name' => 'Sucursal Beta 1',
            'address' => 'Av. Principal 456',
            'commune' => 'Providencia',
        ]);

        $this->tenantAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Tenant',
            'email' => 'admin@gourmetpro.test',
            'password' => 'password123',
            'role' => UserRole::TenantAdmin,
            'is_active' => true,
        ]);

        $this->kitchenOperator = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Chef Jefe',
            'email' => 'chef@gourmetpro.test',
            'password' => 'password123',
            'role' => UserRole::KitchenOperator,
            'is_active' => true,
        ]);

        $this->companyAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'RRHH Alfa',
            'email' => 'rrhh@alfa.test',
            'password' => 'password123',
            'role' => UserRole::CompanyAdmin,
            'is_active' => true,
        ]);

        $this->employeeA = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Juan Empleado Alfa',
            'email' => 'juan@alfa.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $this->employeeB = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Pedro Empleado Beta',
            'email' => 'pedro@beta.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $this->dish = Dish::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Lomo Saltado',
            'category' => 'Fondo',
            'calories_kcal' => 600,
        ]);

        $this->publishedMenu = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => null, // General
            'title' => 'Menú General Publicado',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $this->menuItemA = MenuItem::create([
            'menu_id' => $this->publishedMenu->id,
            'dish_id' => $this->dish->id,
            'option_label' => 'Opción 1',
            'max_quota' => 20,
        ]);

        $this->draftMenu = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => null,
            'title' => 'Menú Borrador Futuro',
            'menu_date' => now()->addDays(5)->toDateString(),
            'is_published' => false,
        ]);

        $this->exclusiveMenuB = Menu::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyB->id,
            'title' => 'Menú Exclusivo Beta',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);
    }

    /* -------------------------------------------------------------------------
     * DISHES ENDPOINTS PERMISSIONS
     * ---------------------------------------------------------------------- */

    public function test_tenant_admin_has_full_crud_on_dishes(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        // Index
        $this->getJson('/api/v1/dishes')->assertOk();

        // Create
        $createRes = $this->postJson('/api/v1/dishes', [
            'name' => 'Charquicán Criollo',
            'category' => 'Fondo',
        ]);
        $createRes->assertCreated();
        $dishId = $createRes->json('id');

        // Show
        $this->getJson("/api/v1/dishes/{$dishId}")->assertOk();

        // Update
        $this->putJson("/api/v1/dishes/{$dishId}", [
            'name' => 'Charquicán con Huevo',
        ])->assertOk();

        // Delete
        $this->deleteJson("/api/v1/dishes/{$dishId}")->assertOk();
    }

    public function test_kitchen_operator_has_read_only_access_to_dishes(): void
    {
        Sanctum::actingAs($this->kitchenOperator);

        // Puede listar y ver detalle
        $this->getJson('/api/v1/dishes')->assertOk();
        $this->getJson("/api/v1/dishes/{$this->dish->id}")->assertOk();

        // No puede crear, actualizar ni eliminar
        $this->postJson('/api/v1/dishes', [
            'name' => 'Nuevo Plato Cocina',
            'category' => 'Fondo',
        ])->assertForbidden();

        $this->putJson("/api/v1/dishes/{$this->dish->id}", [
            'name' => 'Modificado por Cocina',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/dishes/{$this->dish->id}")->assertForbidden();
    }

    public function test_company_admin_and_employee_are_forbidden_from_dishes_catalog(): void
    {
        foreach ([$this->companyAdmin, $this->employeeA] as $user) {
            Sanctum::actingAs($user);

            $this->getJson('/api/v1/dishes')->assertForbidden();
            $this->getJson("/api/v1/dishes/{$this->dish->id}")->assertForbidden();
            $this->postJson('/api/v1/dishes', ['name' => 'X', 'category' => 'Fondo'])->assertForbidden();
            $this->putJson("/api/v1/dishes/{$this->dish->id}", ['name' => 'X'])->assertForbidden();
            $this->deleteJson("/api/v1/dishes/{$this->dish->id}")->assertForbidden();
        }
    }

    /* -------------------------------------------------------------------------
     * MENUS ENDPOINTS PERMISSIONS & VISIBILITY
     * ---------------------------------------------------------------------- */

    public function test_tenant_admin_can_manage_and_publish_menus(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        // Store
        $createRes = $this->postJson('/api/v1/menus', [
            'title' => 'Menú Viernes Especial',
            'menu_date' => now()->addDays(2)->toDateString(),
            'items' => [
                [
                    'dish_id' => $this->dish->id,
                    'option_label' => 'Opción Premium',
                    'max_quota' => 30,
                ],
            ],
        ]);
        $createRes->assertCreated();
        $menuId = $createRes->json('id');

        // Publish
        $this->postJson("/api/v1/menus/{$menuId}/publish")->assertOk();

        // Unpublish
        $this->postJson("/api/v1/menus/{$menuId}/unpublish")->assertOk();

        // Update
        $this->putJson("/api/v1/menus/{$menuId}", [
            'title' => 'Menú Viernes Actualizado',
        ])->assertOk();

        // Delete
        $this->deleteJson("/api/v1/menus/{$menuId}")->assertOk();
    }

    public function test_cannot_publish_menu_without_items(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $emptyMenu = Menu::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Menú Vacío',
            'menu_date' => now()->addDays(4)->toDateString(),
            'is_published' => false,
        ]);

        $response = $this->postJson("/api/v1/menus/{$emptyMenu->id}/publish");

        $response->assertStatus(422);
        $response->assertJson(['message' => 'No se puede publicar un menú sin opciones de platos.']);
    }

    public function test_cannot_delete_menu_with_confirmed_orders(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->employeeA->id,
            'menu_id' => $this->publishedMenu->id,
            'menu_item_id' => $this->menuItemA->id,
            'order_date' => $this->publishedMenu->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'order-12345'),
        ]);

        $response = $this->deleteJson("/api/v1/menus/{$this->publishedMenu->id}");

        $response->assertStatus(422);
        $response->assertJson(['message' => 'No se puede eliminar un menú que ya tiene pedidos confirmados o entregados.']);
    }

    public function test_employee_and_company_admin_only_see_published_and_applicable_menus(): void
    {
        Sanctum::actingAs($this->employeeA);

        // Index
        $response = $this->getJson('/api/v1/menus');
        $response->assertOk();

        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertContains('Menú General Publicado', $titles);
        $this->assertNotContains('Menú Borrador Futuro', $titles); // No publicado
        $this->assertNotContains('Menú Exclusivo Beta', $titles);   // Exclusivo de Company B

        // Show general publicado -> 200
        $this->getJson("/api/v1/menus/{$this->publishedMenu->id}")->assertOk();

        // Show borrador -> 404
        $this->getJson("/api/v1/menus/{$this->draftMenu->id}")->assertNotFound();

        // Show exclusivo de otra empresa -> 404
        $this->getJson("/api/v1/menus/{$this->exclusiveMenuB->id}")->assertNotFound();
    }

    /* -------------------------------------------------------------------------
     * MENU ITEMS PERMISSIONS & BUSINESS CONSTRAINTS
     * ---------------------------------------------------------------------- */

    public function test_only_tenant_admin_can_manage_menu_items(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        // Store item
        $createRes = $this->postJson("/api/v1/menus/{$this->publishedMenu->id}/items", [
            'dish_id' => $this->dish->id,
            'option_label' => 'Opción B Vegana',
            'max_quota' => 15,
        ]);
        $createRes->assertCreated();
        $itemId = $createRes->json('id');

        // Update item
        $this->putJson("/api/v1/menu-items/{$itemId}", [
            'max_quota' => 25,
        ])->assertOk();

        // Destroy item
        $this->deleteJson("/api/v1/menu-items/{$itemId}")->assertOk();

        // Cocina / Empleado no pueden
        Sanctum::actingAs($this->kitchenOperator);
        $this->postJson("/api/v1/menus/{$this->publishedMenu->id}/items", [
            'dish_id' => $this->dish->id,
            'option_label' => 'Opción No Autorizada',
        ])->assertForbidden();
    }

    public function test_cannot_reduce_menu_item_quota_below_confirmed_orders(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        // Creamos 2 pedidos para menuItemA
        for ($i = 0; $i < 2; $i++) {
            $user = User::create([
                'tenant_id' => $this->tenant->id,
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'name' => "Comensal {$i}",
                'email' => "user{$i}@alfa.test",
                'password' => 'password123',
                'role' => UserRole::Employee,
            ]);

            Order::create([
                'tenant_id' => $this->tenant->id,
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'user_id' => $user->id,
                'menu_id' => $this->publishedMenu->id,
                'menu_item_id' => $this->menuItemA->id,
                'order_date' => $this->publishedMenu->menu_date,
                'status' => OrderStatus::Confirmed,
                'qr_code_hash' => hash('sha256', "quota-order-{$i}"),
            ]);
        }

        // Intento fijar el cupo en 1 (menor a los 2 confirmados)
        $response = $this->putJson("/api/v1/menu-items/{$this->menuItemA->id}", [
            'max_quota' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'No puedes fijar el cupo en 1: ya existen 2 pedidos confirmados para esta opción.',
        ]);
    }

    public function test_cannot_delete_menu_item_with_confirmed_orders(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->employeeA->id,
            'menu_id' => $this->publishedMenu->id,
            'menu_item_id' => $this->menuItemA->id,
            'order_date' => $this->publishedMenu->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'item-lock-order'),
        ]);

        $response = $this->deleteJson("/api/v1/menu-items/{$this->menuItemA->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'No se puede eliminar una opción que ya tiene pedidos confirmados. Puedes marcarla como no disponible.',
        ]);
    }

    /* -------------------------------------------------------------------------
     * ORDERS ENDPOINTS PERMISSIONS & SCOPING
     * ---------------------------------------------------------------------- */

    public function test_order_index_scoping_per_role(): void
    {
        $orderA = Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->employeeA->id,
            'menu_id' => $this->publishedMenu->id,
            'menu_item_id' => $this->menuItemA->id,
            'order_date' => $this->publishedMenu->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'hash-a'),
        ]);

        $orderB = Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'user_id' => $this->employeeB->id,
            'menu_id' => $this->publishedMenu->id,
            'menu_item_id' => $this->menuItemA->id,
            'order_date' => $this->publishedMenu->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'hash-b'),
        ]);

        // 1. Employee A solo ve orderA
        Sanctum::actingAs($this->employeeA);
        $resA = $this->getJson('/api/v1/orders');
        $resA->assertOk();
        $idsA = collect($resA->json('data'))->pluck('id')->toArray();
        $this->assertContains($orderA->id, $idsA);
        $this->assertNotContains($orderB->id, $idsA);

        // 2. CompanyAdmin Alfa ve orderA pero no orderB
        Sanctum::actingAs($this->companyAdmin);
        $resCompany = $this->getJson('/api/v1/orders');
        $resCompany->assertOk();
        $idsCompany = collect($resCompany->json('data'))->pluck('id')->toArray();
        $this->assertContains($orderA->id, $idsCompany);
        $this->assertNotContains($orderB->id, $idsCompany);

        // 3. TenantAdmin ve ambos pedidos
        Sanctum::actingAs($this->tenantAdmin);
        $resTenant = $this->getJson('/api/v1/orders');
        $resTenant->assertOk();
        $idsTenant = collect($resTenant->json('data'))->pluck('id')->toArray();
        $this->assertContains($orderA->id, $idsTenant);
        $this->assertContains($orderB->id, $idsTenant);
    }

    public function test_employee_cannot_view_or_cancel_other_employee_order(): void
    {
        $orderB = Order::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'user_id' => $this->employeeB->id,
            'menu_id' => $this->publishedMenu->id,
            'menu_item_id' => $this->menuItemA->id,
            'order_date' => $this->publishedMenu->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'hash-b-private'),
        ]);

        Sanctum::actingAs($this->employeeA);

        // Show de pedido ajeno -> 403 Forbidden
        $this->getJson("/api/v1/orders/{$orderB->id}")->assertForbidden();

        // Cancel de pedido ajeno -> 403 Forbidden
        $this->postJson("/api/v1/orders/{$orderB->id}/cancel", [
            'reason' => 'Intento malicioso',
        ])->assertForbidden();
    }
}
