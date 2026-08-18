<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DishController extends Controller
{
    /**
     * Lista el catálogo de platos del catering (tenant) con filtros opcionales.
     *
     * GET /api/v1/dishes?category=Fondo&active=1&search=lasaña
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isTenantAdmin() || $user->isKitchenOperator(), 403, 'No tienes permisos para ver el catálogo de platos.');

        $dishes = Dish::query()
            ->where('tenant_id', $user->tenant_id)
            ->when($request->filled('category'), fn ($q) => $q->byCategory($request->string('category')))
            ->when($request->boolean('active'), fn ($q) => $q->active())
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return response()->json($dishes);
    }

    /**
     * Registra un nuevo plato/receta en el catálogo del catering.
     *
     * POST /api/v1/dishes
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede crear platos.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'dietary_tags' => ['nullable', 'array'],
            'dietary_tags.*' => ['string', 'max:50'],
            'allergens' => ['nullable', 'array'],
            'allergens.*' => ['string', 'max:50'],
            'calories_kcal' => ['nullable', 'integer', 'min:0'],
            'raw_cost_clp' => ['nullable', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['boolean'],
        ]);

        $dish = Dish::create([...$data, 'tenant_id' => $user->tenant_id]);

        return response()->json($dish, 201);
    }

    /**
     * Muestra el detalle de un plato del catálogo.
     *
     * GET /api/v1/dishes/{dish}
     */
    public function show(Request $request, Dish $dish): JsonResponse
    {
        $user = $request->user();
        abort_unless($dish->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin() || $user->isKitchenOperator(), 403, 'No tienes permisos para ver este plato.');

        return response()->json($dish);
    }

    /**
     * Actualiza los datos de un plato existente.
     *
     * PUT/PATCH /api/v1/dishes/{dish}
     */
    public function update(Request $request, Dish $dish): JsonResponse
    {
        $user = $request->user();
        abort_unless($dish->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede editar platos.');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'dietary_tags' => ['nullable', 'array'],
            'dietary_tags.*' => ['string', 'max:50'],
            'allergens' => ['nullable', 'array'],
            'allergens.*' => ['string', 'max:50'],
            'calories_kcal' => ['nullable', 'integer', 'min:0'],
            'raw_cost_clp' => ['nullable', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['boolean'],
        ]);

        $dish->update($data);

        return response()->json($dish);
    }

    /**
     * Elimina (soft delete) un plato del catálogo.
     * Los menús históricos que lo referencian no se ven afectados.
     *
     * DELETE /api/v1/dishes/{dish}
     */
    public function destroy(Request $request, Dish $dish): JsonResponse
    {
        $user = $request->user();
        abort_unless($dish->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede eliminar platos.');

        $dish->delete();

        return response()->json(['message' => 'Plato eliminado correctamente.']);
    }
}
