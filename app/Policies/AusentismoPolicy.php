<?php

namespace App\Policies;

use App\Models\Ausentismo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AusentismoPolicy
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
        return $user->hasPermissionTo('ausentismo.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ausentismo  $ausentismo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Ausentismo $ausentismo)
    {
        return $user->hasPermissionTo('ausentismo.ver');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('ausentismo.crear');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ausentismo  $ausentismo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Ausentismo $ausentismo)
    {
        return $user->hasPermissionTo('ausentismo.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ausentismo  $ausentismo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Ausentismo $ausentismo)
    {
        return $user->hasPermissionTo('ausentismo.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ausentismo  $ausentismo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Ausentismo $ausentismo)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ausentismo  $ausentismo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Ausentismo $ausentismo)
    {
        //
    }
}
