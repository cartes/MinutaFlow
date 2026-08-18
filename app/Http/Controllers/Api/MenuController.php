<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $user = $request->user();

        $query = Menu::query()
            ->where('tenant_id', $user->tenant_id)
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
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede crear menús.');

        $data = $request->validate([
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('tenant_id', $user->tenant_id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'menu_date' => [
                'required',
                'date',
                // Evita duplicar el menú del mismo día para el mismo alcance (general o por empresa)
                Rule::unique('menus', 'menu_date')
                    ->where('tenant_id', $user->tenant_id)
                    ->where('company_id', $request->input('company_id'))
                    ->whereNull('deleted_at'),
            ],
            'is_published' => ['boolean'],
            'items' => ['nullable', 'array'],
            'items.*.dish_id' => [
                'required',
                Rule::exists('dishes', 'id')->where('tenant_id', $user->tenant_id)->whereNull('deleted_at'),
            ],
            'items.*.option_label' => ['required', 'string', 'max:30'],
            'items.*.max_quota' => ['nullable', 'integer', 'min:1'],
            'items.*.price_extra_clp' => ['nullable', 'integer', 'min:0'],
            'items.*.is_available' => ['boolean'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $menu = DB::transaction(function () use ($data, $user) {
            $menu = Menu::create([
                'tenant_id' => $user->tenant_id,
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
        $user = $request->user();
        abort_unless($menu->tenant_id === $user->tenant_id, 404);

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
    public function update(Request $request, Menu $menu): JsonResponse
    {
        $user = $request->user();
        abort_unless($menu->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede editar menús.');

        $data = $request->validate([
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('tenant_id', $user->tenant_id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'menu_date' => ['sometimes', 'date'],
            'is_published' => ['boolean'],
        ]);

        $menu->update($data);

        return response()->json($menu->load('items.dish'));
    }

    /**
     * Elimina (soft delete) un menú. No se permite si ya tiene pedidos activos.
     *
     * DELETE /api/v1/menus/{menu}
     */
    public function destroy(Request $request, Menu $menu): JsonResponse
    {
        $user = $request->user();
        abort_unless($menu->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede eliminar menús.');

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
    public function publish(Request $request, Menu $menu): JsonResponse
    {
        $user = $request->user();
        abort_unless($menu->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede publicar menús.');

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
    public function unpublish(Request $request, Menu $menu): JsonResponse
    {
        $user = $request->user();
        abort_unless($menu->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede despublicar menús.');

        $menu->update(['is_published' => false]);

        return response()->json($menu);
    }
}
