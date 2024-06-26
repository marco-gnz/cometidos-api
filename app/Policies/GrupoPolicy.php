<?php

namespace App\Policies;

use App\Models\Grupo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GrupoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('grupofirma.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Grupo  $grupo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Grupo $grupo)
    {
        return $user->hasPermissionTo('grupofirma.ver');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('grupofirma.crear');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Grupo  $grupo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Grupo $grupo)
    {
        return $user->hasPermissionTo('grupofirma.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Grupo  $grupo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Grupo $grupo)
    {
        $total_solicitudes = $grupo->solicitudes()->count();
        return $total_solicitudes <= 0 && $user->hasPermissionTo('grupofirma.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Grupo  $grupo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Grupo $grupo)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Grupo  $grupo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Grupo $grupo)
    {
        //
    }
}
