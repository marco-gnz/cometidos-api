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

    /*  public function before($user, $ability)
    {
        return true;
    } */
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

        $status_ok = [
            EstadoProcesoRendicionGasto::STATUS_INGRESADA,
            EstadoProcesoRendicionGasto::STATUS_MODIFICADA,
            EstadoProcesoRendicionGasto::STATUS_RECHAZADO
        ];

        if (in_array($procesoRendicionGasto->status, $status_ok)) {
            return true;
        }
        return false;
    }

    public function updatepago(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        if ($procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }
        $firma = $this->isFirmaDisponibleProcesoRendicionActionPolicy($procesoRendicionGasto, 'rendicion.dias-pago');
        if ($firma->is_firma && $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_APROBADO_N || $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_APROBADO_S) {
            return true;
        }
        return false;
    }

    public function anular(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        if ($procesoRendicionGasto->solicitud->status === Solicitud::STATUS_ANULADO || $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_ANULADO) {
            return false;
        }

        $status_ok = [
            EstadoProcesoRendicionGasto::STATUS_APROBADO_N,
            EstadoProcesoRendicionGasto::STATUS_APROBADO_S,
        ];

        if ($procesoRendicionGasto->user_id_by === $user->id && in_array($procesoRendicionGasto->status, $status_ok)) {
            return false;
        }

        $firma = $this->isFirmaDisponibleProcesoRendicionActionPolicy($procesoRendicionGasto, 'rendicion.firma.anular');
        if ($firma->is_firma || $procesoRendicionGasto->user_id_by === $user->id) {
            return true;
        }
        return false;
    }

    public function rechazar(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        if (
            $procesoRendicionGasto->solicitud->status === Solicitud::STATUS_ANULADO ||
            $procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_ANULADO
        ) {
            return false;
        }

        $status_ok = [
            EstadoProcesoRendicionGasto::STATUS_INGRESADA,
            EstadoProcesoRendicionGasto::STATUS_MODIFICADA,
            EstadoProcesoRendicionGasto::STATUS_APROBADO_JD,
            EstadoProcesoRendicionGasto::STATUS_EN_PROCESO,
            EstadoProcesoRendicionGasto::STATUS_VERIFICADO,
        ];

        $firma = $this->obtenerFirmaDisponibleProcesoRendicion($procesoRendicionGasto, 'rendicion.firma.rechazar');
        if ($firma->is_firma && in_array($procesoRendicionGasto->status, $status_ok)) {
            return true;
        }
        return false;
    }

    public function aprobar(User $user, ProcesoRendicionGasto $procesoRendicionGasto)
    {
        if ($procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }

        $status_rendicion = [
            EstadoProcesoRendicionGasto::STATUS_APROBADO_JD,
            EstadoProcesoRendicionGasto::STATUS_EN_PROCESO,
            EstadoProcesoRendicionGasto::STATUS_ANULADO,
            EstadoProcesoRendicionGasto::STATUS_APROBADO_N,
            EstadoProcesoRendicionGasto::STATUS_APROBADO_S,
        ];
        $status = $procesoRendicionGasto->status;

        if (in_array($status, $status_rendicion)) {
            return false;
        }

        $firma  = $this->obtenerFirmaDisponibleProcesoRendicion($procesoRendicionGasto, 'rendicion.firma.validar');
        if (!$firma->is_firma) {
            return false;
        }

        return true;
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
