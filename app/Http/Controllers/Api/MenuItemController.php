<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    /**
     * Agrega una nueva opción de plato a un menú diario.
     *
     * POST /api/v1/menus/{menu}/items
     */
    public function store(Request $request, Menu $menu): JsonResponse
    {
        $user = $request->user();
        abort_unless($menu->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede modificar las opciones del menú.');

        $data = $request->validate([
            'dish_id' => [
                'required',
                Rule::exists('dishes', 'id')->where('tenant_id', $user->tenant_id)->whereNull('deleted_at'),
            ],
            'option_label' => ['required', 'string', 'max:30'],
            'max_quota' => ['nullable', 'integer', 'min:1'],
            'price_extra_clp' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $item = $menu->items()->create([
            ...$data,
            'sort_order' => $data['sort_order'] ?? $menu->items()->count(),
        ]);

        return response()->json($item->load('dish'), 201);
    }

    /**
     * Actualiza una opción del menú (cupo, copago extra, disponibilidad, etc.).
     *
     * PUT/PATCH /api/v1/menu-items/{menuItem}
     */
    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $user = $request->user();
        abort_unless($menuItem->menu->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede modificar las opciones del menú.');

        $data = $request->validate([
            'dish_id' => [
                'sometimes',
                Rule::exists('dishes', 'id')->where('tenant_id', $user->tenant_id)->whereNull('deleted_at'),
            ],
            'option_label' => ['sometimes', 'string', 'max:30'],
            'max_quota' => ['nullable', 'integer', 'min:1'],
            'price_extra_clp' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // No permitir bajar el cupo por debajo de los pedidos ya confirmados
        if (array_key_exists('max_quota', $data) && $data['max_quota'] !== null) {
            $confirmed = $menuItem->getConfirmedOrdersCount();
            if ($data['max_quota'] < $confirmed) {
                return response()->json([
                    'message' => "No puedes fijar el cupo en {$data['max_quota']}: ya existen {$confirmed} pedidos confirmados para esta opción.",
                ], 422);
            }
        }

        $menuItem->update($data);

        return response()->json($menuItem->load('dish'));
    }

    /**
     * Elimina una opción del menú. No se permite si ya tiene pedidos confirmados.
     *
     * DELETE /api/v1/menu-items/{menuItem}
     */
    public function destroy(Request $request, MenuItem $menuItem): JsonResponse
    {
        $user = $request->user();
        abort_unless($menuItem->menu->tenant_id === $user->tenant_id, 404);
        abort_unless($user->isTenantAdmin(), 403, 'Solo el administrador del catering puede modificar las opciones del menú.');

        if ($menuItem->getConfirmedOrdersCount() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar una opción que ya tiene pedidos confirmados. Puedes marcarla como no disponible.',
            ], 422);
        }

        $menuItem->delete();

        return response()->json(['message' => 'Opción eliminada correctamente.']);
    }
}
