<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    /**
     * Determina si el usuario puede agregar opciones a un menú.
     */
    public function create(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede modificar una opción del menú.
     */
    public function update(User $user, MenuItem $menuItem): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede eliminar una opción del menú.
     */
    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }
}
