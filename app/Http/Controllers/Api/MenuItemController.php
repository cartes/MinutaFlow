<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItem\StoreMenuItemRequest;
use App\Http\Requests\MenuItem\UpdateMenuItemRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;

class MenuItemController extends Controller
{
    /**
     * Agrega una nueva opción de plato a un menú diario.
     *
     * POST /api/v1/menus/{menu}/items
     */
    public function store(StoreMenuItemRequest $request, Menu $menu): JsonResponse
    {
        $this->authorize('create', MenuItem::class);

        $data = $request->validated();

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
    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        abort_unless($menuItem->menu !== null, 404);
        $this->authorize('update', $menuItem);

        $data = $request->validated();

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
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        abort_unless($menuItem->menu !== null, 404);
        $this->authorize('delete', $menuItem);

        if ($menuItem->getConfirmedOrdersCount() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar una opción que ya tiene pedidos confirmados. Puedes marcarla como no disponible.',
            ], 422);
        }

        $menuItem->delete();

        return response()->json(['message' => 'Opción eliminada correctamente.']);
    }
}
