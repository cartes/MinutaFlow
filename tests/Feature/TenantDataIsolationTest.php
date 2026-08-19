<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;
    private User $adminB;
    private Dish $dishA;
    private Dish $dishB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Catering Norte',
            'slug' => 'catering-norte',
            'rut' => '76.111.111-1',
            'billing_email' => 'norte@catering.test',
            'is_active' => true,
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Catering Sur',
            'slug' => 'catering-sur',
            'rut' => '76.222.222-2',
            'billing_email' => 'sur@catering.test',
            'is_active' => true,
        ]);

        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin Norte',
            'email' => 'admin@norte.test',
            'password' => 'password123',
            'role' => UserRole::TenantAdmin,
        ]);

        $this->adminB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Admin Sur',
            'email' => 'admin@sur.test',
            'password' => 'password123',
            'role' => UserRole::TenantAdmin,
        ]);

        $this->dishA = Dish::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Cazuela Vacuno Norte',
            'category' => 'Fondo',
        ]);

        $this->dishB = Dish::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Curanto Sur',
            'category' => 'Fondo',
        ]);
    }

    public function test_tenant_a_cannot_see_dishes_of_tenant_b(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/dishes');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Cazuela Vacuno Norte']);
        $response->assertJsonMissing(['name' => 'Curanto Sur']);
    }

    public function test_tenant_a_gets_404_when_accessing_dish_of_tenant_b(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson("/api/v1/dishes/{$this->dishB->id}");

        $response->assertNotFound();
    }

    public function test_tenant_a_cannot_update_or_delete_dish_of_tenant_b(): void
    {
        Sanctum::actingAs($this->adminA);

        $updateResponse = $this->putJson("/api/v1/dishes/{$this->dishB->id}", [
            'name' => 'Hacked Name',
        ]);
        $updateResponse->assertNotFound();

        $deleteResponse = $this->deleteJson("/api/v1/dishes/{$this->dishB->id}");
        $deleteResponse->assertNotFound();

        $this->assertDatabaseHas('dishes', [
            'id' => $this->dishB->id,
            'name' => 'Curanto Sur',
            'deleted_at' => null,
        ]);
    }

    public function test_creating_dish_automatically_associates_active_tenant(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->postJson('/api/v1/dishes', [
            'name' => 'Pastel de Choclo',
            'category' => 'Fondo',
            'calories_kcal' => 550,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('tenant_id', $this->tenantA->id);

        $this->assertDatabaseHas('dishes', [
            'name' => 'Pastel de Choclo',
            'tenant_id' => $this->tenantA->id,
        ]);
    }

    public function test_tenant_a_cannot_view_or_modify_menus_of_tenant_b(): void
    {
        $menuB = Menu::create([
            'tenant_id' => $this->tenantB->id,
            'title' => 'Menú Secreto Sur',
            'menu_date' => now()->addDays(2)->toDateString(),
            'is_published' => true,
        ]);

        Sanctum::actingAs($this->adminA);

        // Index no debe listar el menú de Tenant B
        $indexResponse = $this->getJson('/api/v1/menus');
        $indexResponse->assertOk();
        $indexResponse->assertJsonMissing(['title' => 'Menú Secreto Sur']);

        // Show directo debe responder 404
        $showResponse = $this->getJson("/api/v1/menus/{$menuB->id}");
        $showResponse->assertNotFound();

        // Update directo debe responder 404
        $updateResponse = $this->putJson("/api/v1/menus/{$menuB->id}", [
            'title' => 'Cambiado por A',
        ]);
        $updateResponse->assertNotFound();
    }

    public function test_inactive_tenant_requests_are_blocked_with_403(): void
    {
        $this->tenantA->update(['is_active' => false]);

        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/dishes');

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'La cuenta de catering no existe o se encuentra inactiva.',
        ]);
    }
}
