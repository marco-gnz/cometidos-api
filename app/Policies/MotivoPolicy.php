<?php

namespace App\Policies;

use App\Models\Motivo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MotivoPolicy
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
     * @param  \App\Models\Motivo  $motivo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Motivo $motivo)
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
     * @param  \App\Models\Motivo  $motivo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Motivo $motivo)
    {
        return $user->hasPermissionTo('configuracion.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Motivo  $motivo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Motivo $motivo)
    {
         return $user->hasPermissionTo('configuracion.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Motivo  $motivo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Motivo $motivo)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Motivo  $motivo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Motivo $motivo)
    {
        //
    }
}
