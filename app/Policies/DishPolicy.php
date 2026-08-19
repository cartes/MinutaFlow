<?php

namespace App\Policies;

use App\Models\Dish;
use App\Models\User;

class DishPolicy
{
    /**
     * Determina si el usuario puede ver la lista de platos.
     */
    public function viewAny(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isKitchenOperator() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede ver un plato específico.
     */
    public function view(User $user, Dish $dish): bool
    {
        return $user->isTenantAdmin() || $user->isKitchenOperator() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede crear platos en el catálogo.
     */
    public function create(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede actualizar un plato.
     */
    public function update(User $user, Dish $dish): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede eliminar un plato.
     */
    public function delete(User $user, Dish $dish): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }
}
