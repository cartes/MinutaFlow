<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    /**
     * Lista los menús según el rol del usuario:
     * - TenantAdmin / KitchenOperator: todos los menús del catering (incluye borradores).
     * - CompanyAdmin / Employee: solo menús publicados visibles para su empresa (generales o exclusivos).
     *
     * GET /api/v1/menus?from=2026-08-18&to=2026-08-24&company_id=...
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Menu::class);

        $user = $request->user();

        $query = Menu::query()
            ->with(['items.dish', 'company:id,name'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('menu_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('menu_date', '<=', $request->date('to')))
            ->orderBy('menu_date');

        if ($user->isTenantAdmin() || $user->isKitchenOperator()) {
            // Vista de gestión del catering: puede filtrar por empresa cliente
            $query->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')));
        } else {
            // Vista comensal / RRHH: solo menús publicados generales o de su propia empresa
            $query->published()->where(function ($q) use ($user) {
                $q->whereNull('company_id')->orWhere('company_id', $user->company_id);
            });
        }

        return response()->json($query->paginate($request->integer('per_page', 25)));
    }

    /**
     * Crea un menú diario, opcionalmente con sus opciones de platos incluidas.
     *
     * POST /api/v1/menus
     */
    public function store(StoreMenuRequest $request): JsonResponse
    {
        $this->authorize('create', Menu::class);

        $data = $request->validated();

        $menu = DB::transaction(function () use ($data) {
            $menu = Menu::create([
                'company_id' => $data['company_id'] ?? null,
                'title' => $data['title'] ?? null,
                'menu_date' => $data['menu_date'],
                'is_published' => $data['is_published'] ?? false,
            ]);

            foreach ($data['items'] ?? [] as $index => $item) {
                $menu->items()->create([...$item, 'sort_order' => $item['sort_order'] ?? $index]);
            }

            return $menu;
        });

        return response()->json($menu->load('items.dish'), 201);
    }

    /**
     * Muestra el detalle de un menú con sus opciones, platos y cupos restantes.
     *
     * GET /api/v1/menus/{menu}
     */
    public function show(Request $request, Menu $menu): JsonResponse
    {
        $this->authorize('view', $menu);

        $user = $request->user();

        // Comensales y RRHH solo pueden ver menús publicados de su alcance
        if (!$user->isTenantAdmin() && !$user->isKitchenOperator()) {
            abort_unless($menu->is_published, 404);
            abort_unless($menu->company_id === null || $menu->company_id === $user->company_id, 404);
        }

        $menu->load('items.dish', 'company:id,name');

        // Adjunta los cupos restantes de cada opción para que el frontend muestre disponibilidad
        $items = $menu->items->map(fn (MenuItem $item) => [
            ...$item->toArray(),
            'remaining_quota' => $item->remainingQuota(),
            'is_sold_out' => $item->isSoldOut(),
        ]);

        return response()->json([...$menu->toArray(), 'items' => $items]);
    }

    /**
     * Actualiza los datos generales de un menú (título, fecha, empresa, publicación).
     *
     * PUT/PATCH /api/v1/menus/{menu}
     */
    public function update(UpdateMenuRequest $request, Menu $menu): JsonResponse
    {
        $this->authorize('update', $menu);

        $menu->update($request->validated());

        return response()->json($menu->load('items.dish'));
    }

    /**
     * Elimina (soft delete) un menú. No se permite si ya tiene pedidos activos.
     *
     * DELETE /api/v1/menus/{menu}
     */
    public function destroy(Menu $menu): JsonResponse
    {
        $this->authorize('delete', $menu);

        if ($menu->orders()->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Delivered])->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un menú que ya tiene pedidos confirmados o entregados.',
            ], 422);
        }

        $menu->delete();

        return response()->json(['message' => 'Menú eliminado correctamente.']);
    }

    /**
     * Publica el menú, haciéndolo visible para los comensales.
     *
     * POST /api/v1/menus/{menu}/publish
     */
    public function publish(Menu $menu): JsonResponse
    {
        $this->authorize('publish', $menu);

        if ($menu->items()->count() === 0) {
            return response()->json([
                'message' => 'No se puede publicar un menú sin opciones de platos.',
            ], 422);
        }

        $menu->update(['is_published' => true]);

        return response()->json($menu->load('items.dish'));
    }

    /**
     * Despublica el menú, ocultándolo de los comensales.
     *
     * POST /api/v1/menus/{menu}/unpublish
     */
    public function unpublish(Menu $menu): JsonResponse
    {
        $this->authorize('publish', $menu);

        $menu->update(['is_published' => false]);

        return response()->json($menu);
    }
}
