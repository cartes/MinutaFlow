<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTenantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_metrics_and_tenants_list(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'admin@minutaflow.cl',
        ]);

        $tenant = Tenant::factory()->create([
            'name' => 'Casino Los Andes',
            'rut' => '76.999.888-7',
            'slug' => 'casino-los-andes',
        ]);

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/superadmin/tenants');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'rut',
                        'slug',
                        'billing_email',
                        'is_active',
                        'companies_count',
                        'branches_count',
                        'users_count',
                        'orders_count',
                    ],
                ],
            ]);

        $metricsResponse = $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/superadmin/metrics');

        $metricsResponse->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_tenants',
                    'active_tenants',
                    'total_companies',
                    'total_branches',
                    'total_users',
                    'total_orders',
                ],
            ]);
    }

    public function test_super_admin_can_create_new_tenant_with_initial_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $payload = [
            'name' => 'Banquetes del Sur SpA',
            'rut' => '77.111.222-3',
            'slug' => 'banquetes-del-sur',
            'billing_email' => 'finanzas@banquetesdelsur.cl',
            'phone' => '+56 9 7777 8888',
            'is_active' => true,
            'admin_name' => 'Felipe Navarro',
            'admin_email' => 'felipe@banquetesdelsur.cl',
            'admin_password' => 'supersecret123',
        ];

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/v1/superadmin/tenants', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Banquetes del Sur SpA')
            ->assertJsonPath('data.rut', '77.111.222-3')
            ->assertJsonPath('data.slug', 'banquetes-del-sur');

        $this->assertDatabaseHas('tenants', [
            'name' => 'Banquetes del Sur SpA',
            'rut' => '77.111.222-3',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'felipe@banquetesdelsur.cl',
            'name' => 'Felipe Navarro',
            'role' => UserRole::TenantAdmin->value,
        ]);
    }

    public function test_super_admin_can_update_and_toggle_tenant_status(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->putJson("/api/v1/superadmin/tenants/{$tenant->id}", [
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'is_active' => false,
        ]);
    }

    public function test_tenant_admin_and_regular_users_cannot_access_superadmin_endpoints(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantAdmin = User::factory()->tenantAdmin()->for($tenant)->create();
        $employee = User::factory()->employee()->for($tenant)->create();

        // Tenant Admin intentando acceder a superadmin
        $this->actingAs($tenantAdmin, 'sanctum')
            ->getJson('/api/v1/superadmin/tenants')
            ->assertForbidden();

        // Empleado intentando acceder a superadmin
        $this->actingAs($employee, 'sanctum')
            ->getJson('/api/v1/superadmin/tenants')
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_superadmin_endpoints(): void
    {
        $this->getJson('/api/v1/superadmin/tenants')
            ->assertUnauthorized();
    }
}
