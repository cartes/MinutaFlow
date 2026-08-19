<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dish\StoreDishRequest;
use App\Http\Requests\Dish\UpdateDishRequest;
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
        $this->authorize('viewAny', Dish::class);

        $dishes = Dish::query()
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
    public function store(StoreDishRequest $request): JsonResponse
    {
        $this->authorize('create', Dish::class);

        $dish = Dish::create($request->validated());

        return response()->json($dish, 201);
    }

    /**
     * Muestra el detalle de un plato del catálogo.
     *
     * GET /api/v1/dishes/{dish}
     */
    public function show(Dish $dish): JsonResponse
    {
        $this->authorize('view', $dish);

        return response()->json($dish);
    }

    /**
     * Actualiza los datos de un plato existente.
     *
     * PUT/PATCH /api/v1/dishes/{dish}
     */
    public function update(UpdateDishRequest $request, Dish $dish): JsonResponse
    {
        $this->authorize('update', $dish);

        $dish->update($request->validated());

        return response()->json($dish);
    }

    /**
     * Elimina (soft delete) un plato del catálogo.
     * Los menús históricos que lo referencian no se ven afectados.
     *
     * DELETE /api/v1/dishes/{dish}
     */
    public function destroy(Dish $dish): JsonResponse
    {
        $this->authorize('delete', $dish);

        $dish->delete();

        return response()->json(['message' => 'Plato eliminado correctamente.']);
    }
}
