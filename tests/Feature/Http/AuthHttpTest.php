<?php

namespace Tests\Feature\Http;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthHttpTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Company $company;
    private Branch $branch;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Catering Santiago',
            'slug' => 'catering-santiago',
            'rut' => '76.111.222-3',
            'billing_email' => 'admin@cateringsantiago.test',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Empresa Cliente SpA',
            'rut' => '77.333.444-5',
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Oficina Central',
            'address' => 'Av. Providencia 1000',
            'commune' => 'Providencia',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cristian Usuario',
            'email' => 'cristian@cliente.test',
            'password' => 'password123',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'cristian@cliente.test',
            'password' => 'password123',
            'device_name' => 'iPhone Test',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'tenant' => ['id', 'name', 'slug'],
                'company' => ['id', 'name'],
                'branch' => ['id', 'name'],
            ],
        ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals('Cristian Usuario', $response->json('user.name'));
        $this->assertEquals($this->tenant->id, $response->json('user.tenant.id'));
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'cristian@cliente.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'noexiste@correo.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'cristian@cliente.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        $response->assertJsonFragment([
            'email' => ['Tu cuenta se encuentra desactivada. Contacta a tu administrador.'],
        ]);
    }

    public function test_authenticated_user_can_logout_and_revokes_token(): void
    {
        $token = $this->user->createToken('test-session');

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $response->assertJson(['message' => 'Sesión cerrada correctamente.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_profile_via_me(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('id', $this->user->id);
        $response->assertJsonPath('email', 'cristian@cliente.test');
        $response->assertJsonPath('tenant.id', $this->tenant->id);
        $response->assertJsonPath('company.id', $this->company->id);
        $response->assertJsonPath('branch.id', $this->branch->id);
    }

    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_user_of_inactive_tenant_is_blocked_on_authenticated_endpoints(): void
    {
        $this->tenant->update(['is_active' => false]);
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'La cuenta de catering no existe o se encuentra inactiva.',
        ]);
    }
}
