<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantManagementController extends Controller
{
    /**
     * Listado de empresas de catering con métricas operativas.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query()
            ->withCount(['companies', 'branches', 'users', 'orders'])
            ->with(['users' => function ($q) {
                $q->where('role', UserRole::TenantAdmin->value)->select(['id', 'tenant_id', 'name', 'email', 'phone', 'is_active']);
            }]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('rut', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('billing_email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $tenants = $query->latest()->get();

        return response()->json([
            'data' => $tenants,
        ]);
    }

    /**
     * Registra una nueva empresa de catering y opcionalmente su administrador inicial.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20', 'unique:tenants,rut'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:tenants,slug'],
            'billing_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['nullable', 'string', 'min:8'],
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Asegurar unicidad si el slug automático colisiona
        $originalSlug = $slug;
        $count = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        $tenant = DB::transaction(function () use ($validated, $slug) {
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'rut' => $validated['rut'],
                'slug' => $slug,
                'billing_email' => $validated['billing_email'],
                'phone' => $validated['phone'] ?? null,
                'timezone' => 'America/Santiago',
                'currency' => 'CLP',
                'is_active' => $validated['is_active'] ?? true,
                'settings' => [
                    'plan' => 'standard',
                    'created_by' => 'super_admin',
                ],
            ]);

            // Si se envió información para el administrador inicial de la concesionaria
            if (!empty($validated['admin_email'])) {
                User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $validated['admin_name'] ?? "Admin {$tenant->name}",
                    'email' => $validated['admin_email'],
                    'password' => Hash::make($validated['admin_password'] ?? 'password'),
                    'role' => UserRole::TenantAdmin,
                    'is_active' => true,
                ]);
            }

            return $tenant;
        });

        $tenant->loadCount(['companies', 'branches', 'users', 'orders']);
        $tenant->load(['users' => function ($q) {
            $q->where('role', UserRole::TenantAdmin->value)->select(['id', 'tenant_id', 'name', 'email', 'phone', 'is_active']);
        }]);

        return response()->json([
            'message' => 'Empresa de catering registrada exitosamente.',
            'data' => $tenant,
        ], 201);
    }

    /**
     * Consulta el detalle de una empresa de catering.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->loadCount(['companies', 'branches', 'users', 'orders']);
        $tenant->load([
            'companies.branches',
            'users' => function ($q) {
                $q->select(['id', 'tenant_id', 'name', 'email', 'phone', 'role', 'is_active']);
            },
        ]);

        return response()->json([
            'data' => $tenant,
        ]);
    }

    /**
     * Actualiza información o estado de una empresa de catering.
     */
    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'rut' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('tenants', 'rut')->ignore($tenant->id)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'billing_email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['slug']) && !empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $tenant->update($validated);

        $tenant->loadCount(['companies', 'branches', 'users', 'orders']);
        $tenant->load(['users' => function ($q) {
            $q->where('role', UserRole::TenantAdmin->value)->select(['id', 'tenant_id', 'name', 'email', 'phone', 'is_active']);
        }]);

        return response()->json([
            'message' => 'Empresa de catering actualizada correctamente.',
            'data' => $tenant,
        ]);
    }

    /**
     * Cambia el estado de activación o desactiva un catering.
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->update(['is_active' => false]);

        return response()->json([
            'message' => 'Empresa de catering pausada/desactivada correctamente.',
            'data' => $tenant,
        ]);
    }

    /**
     * Métricas globales de la plataforma SaaS MinutaFlow.
     */
    public function metrics(): JsonResponse
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $totalCompanies = Company::count();
        $totalBranches = Branch::count();
        $totalUsers = User::count();
        $totalOrders = Order::count();

        return response()->json([
            'data' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'total_companies' => $totalCompanies,
                'total_branches' => $totalBranches,
                'total_users' => $totalUsers,
                'total_orders' => $totalOrders,
            ],
        ]);
    }
}
