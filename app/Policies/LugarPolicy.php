<?php

namespace App\Policies;

use App\Models\Lugar;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LugarPolicy
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
        return $user->hasPermissionTo('configuracion.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lugar  $lugar
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Lugar $lugar)
    {
        return $user->hasPermissionTo('configuracion.ver');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('configuracion.crear');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lugar  $lugar
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Lugar $lugar)
    {
        return $user->hasPermissionTo('configuracion.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lugar  $lugar
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Lugar $lugar)
    {
        return $user->hasPermissionTo('configuracion.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lugar  $lugar
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Lugar $lugar)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lugar  $lugar
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Lugar $lugar)
    {
        //
    }
}
