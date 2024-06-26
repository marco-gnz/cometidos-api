<?php

namespace App\Policies;

use App\Models\Concepto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConceptoPolicy
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
        return $user->hasPermissionTo('usuarioespecial.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Concepto  $concepto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Concepto $concepto)
    {
        return $user->hasPermissionTo('usuarioespecial.ver');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('usuarioespecial.crear');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Concepto  $concepto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Concepto $concepto)
    {
        return $user->hasPermissionTo('usuarioespecial.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Concepto  $concepto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Concepto $concepto)
    {
        return $user->hasPermissionTo('usuarioespecial.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Concepto  $concepto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Concepto $concepto)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Concepto  $concepto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Concepto $concepto)
    {
        //
    }
}
