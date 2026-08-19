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

class TenantHttpIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $admin1;
    private User $admin2;
    private User $employee1;
    private User $employee2;
    private Company $company1;
    private Company $company2;
    private Branch $branch1;
    private Branch $branch2;
    private Dish $dish1;
    private Dish $dish2;
    private Menu $menu1;
    private Menu $menu2;
    private MenuItem $item1;
    private MenuItem $item2;
    private Order $order1;
    private Order $order2;

    protected function setUp(): void
    {
        parent::setUp();

        // Tenant 1
        $this->tenant1 = Tenant::create([
            'name' => 'Catering Santiago Norte',
            'slug' => 'catering-stgo-norte',
            'rut' => '76.111.111-1',
            'billing_email' => 'admin@norte.test',
            'is_active' => true,
        ]);

        $this->company1 = Company::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Cliente Norte SpA',
            'rut' => '77.111.111-K',
        ]);

        $this->branch1 = Branch::create([
            'tenant_id' => $this->tenant1->id,
            'company_id' => $this->company1->id,
            'name' => 'Sucursal Norte',
            'address' => 'Recoleta 100',
            'commune' => 'Recoleta',
        ]);

        $this->admin1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Admin Norte',
            'email' => 'admin@norte.test',
            'password' => 'password123',
            'role' => UserRole::TenantAdmin,
            'is_active' => true,
        ]);

        $this->employee1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Comensal Norte',
            'email' => 'comensal@norte.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $this->dish1 = Dish::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Cazuela Norteña',
            'category' => 'Fondo',
        ]);

        $this->menu1 = Menu::create([
            'tenant_id' => $this->tenant1->id,
            'title' => 'Menú Norte',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $this->item1 = MenuItem::create([
            'menu_id' => $this->menu1->id,
            'dish_id' => $this->dish1->id,
            'option_label' => 'Opción Norte 1',
            'max_quota' => 20,
        ]);

        $this->order1 = Order::create([
            'tenant_id' => $this->tenant1->id,
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->employee1->id,
            'menu_id' => $this->menu1->id,
            'menu_item_id' => $this->item1->id,
            'order_date' => $this->menu1->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'qr-tenant-1'),
        ]);

        // Tenant 2
        $this->tenant2 = Tenant::create([
            'name' => 'Catering Santiago Sur',
            'slug' => 'catering-stgo-sur',
            'rut' => '76.222.222-2',
            'billing_email' => 'admin@sur.test',
            'is_active' => true,
        ]);

        $this->company2 = Company::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Cliente Sur SpA',
            'rut' => '77.222.222-K',
        ]);

        $this->branch2 = Branch::create([
            'tenant_id' => $this->tenant2->id,
            'company_id' => $this->company2->id,
            'name' => 'Sucursal Sur',
            'address' => 'San Bernardo 200',
            'commune' => 'San Bernardo',
        ]);

        $this->admin2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Admin Sur',
            'email' => 'admin@sur.test',
            'password' => 'password123',
            'role' => UserRole::TenantAdmin,
            'is_active' => true,
        ]);

        $this->employee2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'company_id' => $this->company2->id,
            'branch_id' => $this->branch2->id,
            'name' => 'Comensal Sur',
            'email' => 'comensal@sur.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $this->dish2 = Dish::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Pastel Sur',
            'category' => 'Fondo',
        ]);

        $this->menu2 = Menu::create([
            'tenant_id' => $this->tenant2->id,
            'title' => 'Menú Sur',
            'menu_date' => now()->toDateString(),
            'is_published' => true,
        ]);

        $this->item2 = MenuItem::create([
            'menu_id' => $this->menu2->id,
            'dish_id' => $this->dish2->id,
            'option_label' => 'Opción Sur 1',
            'max_quota' => 20,
        ]);

        $this->order2 = Order::create([
            'tenant_id' => $this->tenant2->id,
            'company_id' => $this->company2->id,
            'branch_id' => $this->branch2->id,
            'user_id' => $this->employee2->id,
            'menu_id' => $this->menu2->id,
            'menu_item_id' => $this->item2->id,
            'order_date' => $this->menu2->menu_date,
            'status' => OrderStatus::Confirmed,
            'qr_code_hash' => hash('sha256', 'qr-tenant-2'),
        ]);
    }

    /* -------------------------------------------------------------------------
     * DISHES ISOLATION
     * ---------------------------------------------------------------------- */

    public function test_dishes_index_only_lists_active_tenant_dishes(): void
    {
        Sanctum::actingAs($this->admin1);

        $response = $this->getJson('/api/v1/dishes');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Cazuela Norteña']);
        $response->assertJsonMissing(['name' => 'Pastel Sur']);
    }

    public function test_cannot_access_or_modify_dish_of_another_tenant(): void
    {
        Sanctum::actingAs($this->admin1);

        // Show otro tenant -> 404
        $this->getJson("/api/v1/dishes/{$this->dish2->id}")->assertNotFound();

        // Update otro tenant -> 404
        $this->putJson("/api/v1/dishes/{$this->dish2->id}", ['name' => 'Hacked'])->assertNotFound();

        // Delete otro tenant -> 404
        $this->deleteJson("/api/v1/dishes/{$this->dish2->id}")->assertNotFound();
    }

    public function test_dish_creation_forces_authenticated_tenant_id(): void
    {
        Sanctum::actingAs($this->admin1);

        // Intento inyectar tenant_id de Tenant 2 en el payload
        $response = $this->postJson('/api/v1/dishes', [
            'tenant_id' => $this->tenant2->id,
            'name' => 'Plato Seguro',
            'category' => 'Fondo',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('tenant_id', $this->tenant1->id);

        $this->assertDatabaseHas('dishes', [
            'name' => 'Plato Seguro',
            'tenant_id' => $this->tenant1->id,
        ]);
    }

    /* -------------------------------------------------------------------------
     * MENUS & MENU ITEMS ISOLATION
     * ---------------------------------------------------------------------- */

    public function test_menus_index_only_lists_active_tenant_menus(): void
    {
        Sanctum::actingAs($this->admin1);

        $response = $this->getJson('/api/v1/menus');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Menú Norte']);
        $response->assertJsonMissing(['title' => 'Menú Sur']);
    }

    public function test_cannot_access_or_modify_menu_of_another_tenant(): void
    {
        Sanctum::actingAs($this->admin1);

        $this->getJson("/api/v1/menus/{$this->menu2->id}")->assertNotFound();
        $this->putJson("/api/v1/menus/{$this->menu2->id}", ['title' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/v1/menus/{$this->menu2->id}")->assertNotFound();
        $this->postJson("/api/v1/menus/{$this->menu2->id}/publish")->assertNotFound();
        $this->postJson("/api/v1/menus/{$this->menu2->id}/unpublish")->assertNotFound();
    }

    public function test_cannot_modify_or_delete_menu_item_of_another_tenant(): void
    {
        Sanctum::actingAs($this->admin1);

        $this->putJson("/api/v1/menu-items/{$this->item2->id}", [
            'max_quota' => 50,
        ])->assertNotFound();

        $this->deleteJson("/api/v1/menu-items/{$this->item2->id}")->assertNotFound();
    }

    /* -------------------------------------------------------------------------
     * ORDERS ISOLATION
     * ---------------------------------------------------------------------- */

    public function test_orders_index_only_lists_active_tenant_orders(): void
    {
        Sanctum::actingAs($this->admin1);

        $response = $this->getJson('/api/v1/orders');

        $response->assertOk();
        $orderIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->order1->id, $orderIds);
        $this->assertNotContains($this->order2->id, $orderIds);
    }

    public function test_cannot_view_or_cancel_order_of_another_tenant(): void
    {
        Sanctum::actingAs($this->admin1);

        $this->getJson("/api/v1/orders/{$this->order2->id}")->assertNotFound();

        $this->postJson("/api/v1/orders/{$this->order2->id}/cancel", [
            'reason' => 'Intento cross-tenant',
        ])->assertNotFound();
    }

    /* -------------------------------------------------------------------------
     * INACTIVE TENANT GLOBAL BLOCK
     * ---------------------------------------------------------------------- */

    public function test_requests_from_inactive_tenant_are_blocked_globally(): void
    {
        $this->tenant1->update(['is_active' => false]);

        Sanctum::actingAs($this->admin1);

        $response = $this->getJson('/api/v1/menus');

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'La cuenta de catering no existe o se encuentra inactiva.',
        ]);
    }
}
