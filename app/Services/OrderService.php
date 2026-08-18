<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Toma un pedido para un comensal, validando cutoff, cupo y duplicados.
     *
     * @throws ValidationException
     */
    public function placeOrder(User $user, string $menuItemId, string $orderDate, ?string $notes = null): Order
    {
        $orderDate = Carbon::parse($orderDate)->toDateString();

        if (!$user->branch_id) {
            throw ValidationException::withMessages([
                'user' => 'El usuario no tiene una sucursal de despacho asignada.',
            ]);
        }

        $menuItem = MenuItem::with('menu')->find($menuItemId);

        if (!$menuItem) {
            throw ValidationException::withMessages([
                'menu_item_id' => 'La opción de menú seleccionada no existe.',
            ]);
        }

        $menu = $menuItem->menu;

        $this->assertMenuBelongsToUser($menu, $user);
        $this->assertMenuMatchesDate($menu, $orderDate);
        $this->assertWithinCutoff($user, $orderDate);

        return DB::transaction(function () use ($menuItem, $menu, $user, $orderDate, $notes) {
            // Bloquea la fila del menu_item: cualquier otra transacción que quiera
            // pedir esta misma opción debe esperar a que esta termine, así el conteo
            // de cupos leído a continuación siempre es confiable.
            /** @var MenuItem $lockedItem */
            $lockedItem = MenuItem::where('id', $menuItem->id)->lockForUpdate()->firstOrFail();

            if (!$lockedItem->is_available) {
                throw ValidationException::withMessages([
                    'menu_item_id' => 'Esta opción ya no está disponible.',
                ]);
            }

            if ($lockedItem->max_quota !== null) {
                $confirmedCount = Order::where('menu_item_id', $lockedItem->id)
                    ->where('status', OrderStatus::Confirmed)
                    ->count();

                if ($confirmedCount >= $lockedItem->max_quota) {
                    throw ValidationException::withMessages([
                        'menu_item_id' => 'Se agotó el cupo para esta opción.',
                    ]);
                }
            }

            $subsidy = (float) $user->company->subsidy_percentage;
            $copay = (int) round($lockedItem->price_extra_clp * (1 - $subsidy / 100));

            try {
                return Order::create([
                    'tenant_id' => $user->tenant_id,
                    'company_id' => $user->company_id,
                    'branch_id' => $user->branch_id,
                    'user_id' => $user->id,
                    'menu_id' => $menu->id,
                    'menu_item_id' => $lockedItem->id,
                    'order_date' => $orderDate,
                    'status' => OrderStatus::Confirmed,
                    'copay_amount_clp' => $copay,
                    'notes' => $notes,
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueConstraintViolation($exception)) {
                    throw ValidationException::withMessages([
                        'order_date' => 'Ya tienes un pedido registrado para ese día.',
                    ]);
                }

                throw $exception;
            }
        });
    }

    /**
     * Cancela un pedido dentro del plazo permitido (mismo horario de cutoff de la empresa).
     *
     * @throws ValidationException
     */
    public function cancelOrder(Order $order, User $user, ?string $reason = null): Order
    {
        if ($order->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'order' => 'No puedes cancelar un pedido que no es tuyo.',
            ]);
        }

        if ($order->status !== OrderStatus::Confirmed) {
            throw ValidationException::withMessages([
                'order' => 'Este pedido ya no se puede cancelar.',
            ]);
        }

        $this->assertWithinCutoff($user, $order->order_date->toDateString());

        $order->cancel($reason);

        return $order->fresh();
    }

    protected function assertMenuBelongsToUser($menu, User $user): void
    {
        if (!$menu || $menu->tenant_id !== $user->tenant_id) {
            throw ValidationException::withMessages([
                'menu_item_id' => 'La opción de menú seleccionada no pertenece a tu organización.',
            ]);
        }

        if ($menu->company_id !== null && $menu->company_id !== $user->company_id) {
            throw ValidationException::withMessages([
                'menu_item_id' => 'Esta opción de menú no está disponible para tu empresa.',
            ]);
        }

        if (!$menu->is_published) {
            throw ValidationException::withMessages([
                'menu_item_id' => 'Este menú todavía no está publicado.',
            ]);
        }
    }

    protected function assertMenuMatchesDate($menu, string $orderDate): void
    {
        if ($menu->menu_date->toDateString() !== $orderDate) {
            throw ValidationException::withMessages([
                'order_date' => 'La fecha del pedido no coincide con la fecha del menú.',
            ]);
        }
    }

    /**
     * Valida que "ahora" esté dentro del plazo permitido por la empresa del usuario:
     * cutoff_time del día (order_date - cutoff_days_in_advance).
     */
    protected function assertWithinCutoff(User $user, string $orderDate): void
    {
        $company = $user->company;

        if (!$company) {
            throw ValidationException::withMessages([
                'user' => 'El usuario no tiene una empresa cliente asociada.',
            ]);
        }

        $deadline = Carbon::parse($orderDate)
            ->subDays($company->cutoff_days_in_advance)
            ->setTimeFromTimeString($company->cutoff_time);

        if (now()->greaterThan($deadline)) {
            throw ValidationException::withMessages([
                'order_date' => "El plazo para pedir/cancelar ese día venció el {$deadline->format('d/m/Y H:i')}.",
            ]);
        }
    }

    protected function isUniqueConstraintViolation(QueryException $exception): bool
    {
        // 23000: violación de integridad genérica (MySQL/SQLite/Postgres)
        return $exception->getCode() === '23000';
    }
}
