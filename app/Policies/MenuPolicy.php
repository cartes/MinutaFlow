<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    /**
     * Determina si el usuario puede listar menús.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede ver un menú específico.
     */
    public function view(User $user, Menu $menu): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede crear un menú diario.
     */
    public function create(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede actualizar un menú.
     */
    public function update(User $user, Menu $menu): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede eliminar un menú.
     */
    public function delete(User $user, Menu $menu): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determina si el usuario puede publicar o despublicar un menú.
     */
    public function publish(User $user, Menu $menu): bool
    {
        return $user->isTenantAdmin() || $user->isSuperAdmin();
    }
}
