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
        //
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
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
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
        return true;
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
        if($total_solicitudes > 0){
            return false;
        }
        return true;
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
