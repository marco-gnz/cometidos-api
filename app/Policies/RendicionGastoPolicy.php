<?php

namespace App\Policies;

use App\Models\EstadoProcesoRendicionGasto;
use App\Models\RendicionGasto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RendicionGastoPolicy
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
     * @param  \App\Models\RendicionGasto  $rendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, RendicionGasto $rendicionGasto)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RendicionGasto  $rendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, RendicionGasto $rendicionGasto)
    {
        if ($rendicionGasto->procesoRendicionGasto->status !== EstadoProcesoRendicionGasto::STATUS_APROBADO_N && $rendicionGasto->procesoRendicionGasto->status !== EstadoProcesoRendicionGasto::STATUS_APROBADO_S  && $rendicionGasto->procesoRendicionGasto->status !== EstadoProcesoRendicionGasto::STATUS_ANULADO) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RendicionGasto  $rendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, RendicionGasto $rendicionGasto)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RendicionGasto  $rendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, RendicionGasto $rendicionGasto)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RendicionGasto  $rendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, RendicionGasto $rendicionGasto)
    {
        //
    }
}
