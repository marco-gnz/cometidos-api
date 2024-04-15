<?php

namespace App\Policies;

use App\Models\EstadoProcesoRendicionGasto;
use App\Models\ProcesoRendicionGasto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\FirmaDisponibleTrait;

class ProcesoRendicionGastoPolicy
{
    use HandlesAuthorization, FirmaDisponibleTrait;

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
     * @param  \App\Models\ProcesoRendicionGasto  $procesoRendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
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
     * @param  \App\Models\ProcesoRendicionGasto  $procesoRendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        if (!$user->id || !$procesoRendicionGasto->user_id_by) {
            return false;
        }

        if ($procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_INGRESADA || $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_MODIFICADA) {
            return true;
        }
        return false;
    }

    public function updatepago(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        $firma = $this->obtenerFirmaDisponibleProcesoRendicionPago($procesoRendicionGasto);
        if ($firma->is_firma && $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_APROBADO_N || $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_APROBADO_S) {
            return true;
        }
        return false;
    }

    public function anular(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        $firma = $this->obtenerFirmaDisponibleProcesoRendicionAnular($procesoRendicionGasto);
        if ($firma->is_firma) {
            return true;
        }
        return false;
    }

    public function aprobar(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        $firma = $this->obtenerFirmaDisponibleProcesoRendicion($procesoRendicionGasto);
        if ($firma->is_firma) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProcesoRendicionGasto  $procesoRendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        if (!$user->id || !$procesoRendicionGasto->user_id_by) {
            return false;
        }

        if ($procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_INGRESADA || $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_MODIFICADA) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProcesoRendicionGasto  $procesoRendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProcesoRendicionGasto  $procesoRendicionGasto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        //
    }
}
