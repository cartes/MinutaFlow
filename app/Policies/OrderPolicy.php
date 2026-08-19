<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determina si el usuario puede listar pedidos.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede ver un pedido específico.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isTenantAdmin() || $user->isKitchenOperator() || $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin() && $user->company_id === $order->company_id) {
            return true;
        }

        return $user->id === $order->user_id;
    }

    /**
     * Determina si el usuario puede realizar un pedido.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede cancelar un pedido.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->isTenantAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return $user->id === $order->user_id;
    }

    /**
     * Determina si el usuario puede escanear/entregar pedidos.
     */
    public function deliver(User $user): bool
    {
        return $user->isKitchenOperator() || $user->isTenantAdmin() || $user->isSuperAdmin();
    }
}
