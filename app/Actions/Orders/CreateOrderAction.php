<?php

namespace App\Actions\Orders;

use App\Exceptions\SoldOutException;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateOrderAction
{
    /**
     * Crea un pedido dentro de una transacción con bloqueo pesimista (lockForUpdate)
     * sobre el MenuItem para prevenir condiciones de carrera y sobreventa de cupos.
     *
     * @param array<string, mixed> $data
     * @throws SoldOutException
     * @throws InvalidArgumentException
     */
    public function execute(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $menuItemId = $data['menu_item_id'] ?? null;

            if (!$menuItemId) {
                throw new InvalidArgumentException('El campo menu_item_id es obligatorio.');
            }

            // 1. Bloqueo exclusivo de la fila del MenuItem durante la transacción
            /** @var MenuItem $menuItem */
            $menuItem = MenuItem::query()
                ->where('id', $menuItemId)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Validación de disponibilidad del plato
            if (!$menuItem->is_available) {
                throw new SoldOutException("La opción '{$menuItem->option_label}' no se encuentra disponible.");
            }

            // 3. Validación atómica de cupos máximos
            if ($menuItem->isSoldOut()) {
                throw new SoldOutException("La opción '{$menuItem->option_label}' ha agotado sus cupos disponibles.");
            }

            // 4. Autocompletar datos dependientes del MenuItem si no se especificaron
            if (!isset($data['menu_id'])) {
                $data['menu_id'] = $menuItem->menu_id;
            }

            if (!isset($data['copay_amount_clp'])) {
                $data['copay_amount_clp'] = $menuItem->price_extra_clp;
            }

            // 5. Creación segura de la orden
            return Order::create($data);
        });
    }
}
