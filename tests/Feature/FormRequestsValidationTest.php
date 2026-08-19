<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FormRequestsValidationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Company $company;
    private Branch $branch;
    private User $tenantAdmin;
    private User $employee;

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

        $this->tenantAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@catering.test',
            'password' => bcrypt('secret123'),
            'role' => UserRole::TenantAdmin,
            'is_active' => true,
        ]);

        $this->employee = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Employee User',
            'email' => 'employee@techcorp.test',
            'password' => bcrypt('secret123'),
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);
    }

    public function test_login_request_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_store_dish_request_validates_required_name(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/dishes', [
                'description' => 'Un plato sin nombre',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_dish_request_validates_types(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $dish = Dish::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Lasaña boloñesa',
            'category' => 'Fondo',
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/v1/dishes/{$dish->id}", [
                'calories_kcal' => -50,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['calories_kcal']);
    }

    public function test_store_menu_request_validates_menu_date_and_items(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/menus', [
                'title' => 'Menú de Prueba',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['menu_date']);
    }

    public function test_store_menu_item_request_validates_dish_and_label(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $menu = Menu::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Menú Hoy',
            'menu_date' => now()->addDays(2)->toDateString(),
            'is_published' => false,
        ]);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson("/api/v1/menus/{$menu->id}/items", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dish_id', 'option_label']);
    }

    public function test_store_order_request_validates_menu_item_id(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['menu_item_id']);
    }

    public function test_scan_delivery_request_validates_qr_code_hash(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/delivery/scan', [
                'qr_code_hash' => 'hash_demasiado_corto',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['qr_code_hash']);
    }
}
