<?php

namespace Tests\Unit;

use App\Models\Dish;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TenantManager $tenantManager;
    private Tenant $tenant1;
    private Tenant $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantManager = app(TenantManager::class);

        $this->tenant1 = Tenant::create([
            'name' => 'Catering Alpha',
            'slug' => 'catering-alpha',
            'rut' => '76.111.111-1',
            'billing_email' => 'alpha@test.com',
            'is_active' => true,
        ]);

        $this->tenant2 = Tenant::create([
            'name' => 'Catering Beta',
            'slug' => 'catering-beta',
            'rut' => '76.222.222-2',
            'billing_email' => 'beta@test.com',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        $this->tenantManager->clear();
        parent::tearDown();
    }

    public function test_tenant_manager_can_set_and_get_tenant(): void
    {
        $this->tenantManager->setTenantId($this->tenant1->id);

        $this->assertTrue($this->tenantManager->hasTenant());
        $this->assertSame($this->tenant1->id, $this->tenantManager->getTenantId());
        $this->assertSame('Catering Alpha', $this->tenantManager->getTenant()->name);

        $this->tenantManager->clear();
        $this->assertFalse($this->tenantManager->hasTenant());
        $this->assertNull($this->tenantManager->getTenantId());
    }

    public function test_tenant_manager_run_with_tenant_temporarily_switches_context(): void
    {
        $this->tenantManager->setTenantId($this->tenant1->id);

        $result = $this->tenantManager->runWithTenant($this->tenant2->id, function () {
            return $this->tenantManager->getTenantId();
        });

        $this->assertSame($this->tenant2->id, $result);
        $this->assertSame($this->tenant1->id, $this->tenantManager->getTenantId());
    }

    public function test_belongs_to_tenant_trait_auto_assigns_tenant_id_on_creation(): void
    {
        $this->tenantManager->setTenantId($this->tenant1->id);

        $dish = Dish::create([
            'name' => 'Lentejas Caseras',
            'category' => 'Fondo',
        ]);

        $this->assertSame($this->tenant1->id, $dish->tenant_id);
    }

    public function test_global_scope_filters_queries_by_active_tenant(): void
    {
        // Crear platos directamente para cada tenant
        Dish::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Plato Alpha',
            'category' => 'Fondo',
        ]);

        Dish::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Plato Beta',
            'category' => 'Fondo',
        ]);

        // Bajo contexto de Tenant 1, solo se ve Plato Alpha
        $this->tenantManager->setTenantId($this->tenant1->id);
        $dishesT1 = Dish::all();
        $this->assertCount(1, $dishesT1);
        $this->assertSame('Plato Alpha', $dishesT1->first()->name);

        // Bajo contexto de Tenant 2, solo se ve Plato Beta
        $this->tenantManager->setTenantId($this->tenant2->id);
        $dishesT2 = Dish::all();
        $this->assertCount(1, $dishesT2);
        $this->assertSame('Plato Beta', $dishesT2->first()->name);

        // Sin contexto de tenant o quitando el global scope, se ven ambos
        $this->tenantManager->clear();
        $allDishes = Dish::withoutGlobalScope(TenantScope::class)->get();
        $this->assertCount(2, $allDishes);
    }
}
