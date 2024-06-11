<?php

namespace App\Policies;

use App\Models\EstadoProcesoRendicionGasto;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\FirmaDisponibleTrait;
use Illuminate\Support\Facades\Log;

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
        if (!$user->is_rendicion) {
            return false;
        }

        return true;
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
        if ($procesoRendicionGasto->solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

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
        if ($procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }
        $firma = $this->isFirmaDisponibleActionPolicy($procesoRendicionGasto->solicitud, 'rendicion.dias-pago');
        if ($firma->is_firma && $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_APROBADO_N || $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_APROBADO_S) {
            return true;
        }
        return false;
    }

    public function anular(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        if ($procesoRendicionGasto->solicitud->status === Solicitud::STATUS_ANULADO ||
        $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_ANULADO) {
            return false;
        }

        $firma = $this->isFirmaDisponibleActionPolicy($procesoRendicionGasto->solicitud, 'rendicion.firma.anular');
        if ($firma->is_firma || $procesoRendicionGasto->user_id_by === $user->id) {
            return true;
        }
        return false;
    }

    public function aprobar(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        if ($procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }
        $status = $procesoRendicionGasto->status;

        $firma  = $this->isFirmaDisponibleActionPolicy($procesoRendicionGasto->solicitud, 'rendicion.firma.validar');
        if (($firma->is_firma) && ($firma->firma->role_id === 3 && $status === EstadoProcesoRendicionGasto::STATUS_INGRESADA || $status === EstadoProcesoRendicionGasto::STATUS_MODIFICADA)) {
            return true;
        }

        if (($firma->is_firma) && ($firma->firma->role_id === 7 && $status === EstadoProcesoRendicionGasto::STATUS_VERIFICADO)) {
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
        if ($procesoRendicionGasto->solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

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
