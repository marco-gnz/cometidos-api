<?php

namespace App\Policies;

use App\Models\Convenio;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConvenioPolicy
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
        return $user->hasPermissionTo('convenio.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Convenio  $convenio
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Convenio $convenio)
    {
        return $user->hasPermissionTo('convenio.ver');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('convenio.crear');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Convenio  $convenio
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Convenio $convenio)
    {
        return $user->hasPermissionTo('convenio.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Convenio  $convenio
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Convenio $convenio)
    {
        $total_solicitudes = $convenio->solicitudes()->count();
        return $total_solicitudes <= 0 && $user->hasPermissionTo('convenio.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Convenio  $convenio
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Convenio $convenio)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Convenio  $convenio
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Convenio $convenio)
    {
        //
    }
}
