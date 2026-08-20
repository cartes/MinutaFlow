<?php

namespace Tests\Feature\Security;

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

class FormSanitizationAndInjectionTest extends TestCase
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

    public function test_dish_creation_sanitizes_xss_scripts_and_html_tags(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $payload = [
            'name' => '  Cazuela Criolla <script>alert("XSS")</script>  ',
            'description' => '<p>Carne tierna con <span onclick="alert(1)">choclo</span> y zapallo</p>',
            'category' => '<b>Fondo</b>',
            'dietary_tags' => ['<i>Sin Gluten</i>', '<script>bad()</script>Keto'],
            'allergens' => ['<u>Lactosa</u>'],
            'calories_kcal' => 550,
            'is_active' => true,
        ];

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/dishes', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('dishes', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Cazuela Criolla alert("XSS")',
            'description' => 'Carne tierna con choclo y zapallo',
            'category' => 'Fondo',
        ]);

        $dish = Dish::where('tenant_id', $this->tenant->id)->first();
        $this->assertEquals(['Sin Gluten', 'bad()Keto'], $dish->dietary_tags);
        $this->assertEquals(['Lactosa'], $dish->allergens);
    }

    public function test_menu_and_order_creation_cleans_dangerous_characters(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $dish = Dish::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Arroz con Pollo',
            'category' => 'Fondo',
            'is_active' => true,
        ]);

        $responseMenu = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/menus', [
                'title' => '  Menú del Día <img src=x onerror=steal()>  ',
                'menu_date' => now()->addDays(3)->toDateString(),
                'is_published' => true,
                'items' => [
                    [
                        'dish_id' => $dish->id,
                        'option_label' => '<b>Opción 1</b>',
                        'max_quota' => 50,
                        'is_available' => true,
                    ],
                ],
            ]);

        $responseMenu->assertCreated();

        $this->assertDatabaseHas('menus', [
            'tenant_id' => $this->tenant->id,
            'title' => 'Menú del Día',
        ]);

        $this->assertDatabaseHas('menu_items', [
            'option_label' => 'Opción 1',
        ]);

        $menuItem = MenuItem::first();

        // Probar orden como comensal
        Sanctum::actingAs($this->employee);

        $responseOrder = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/orders', [
                'menu_item_id' => $menuItem->id,
                'notes' => '  Sin ensalada por favor <script>document.cookie</script>  ',
            ]);

        $responseOrder->assertCreated();

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->employee->id,
            'notes' => 'Sin ensalada por favor document.cookie',
        ]);
    }

    public function test_login_sanitizes_email_with_spaces_and_uppercase(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => '  ADMIN@catering.TEST  ',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }
}
