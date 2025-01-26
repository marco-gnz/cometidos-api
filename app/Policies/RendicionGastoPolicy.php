<?php

namespace App\Policies;

use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoRendicionGasto;
use App\Models\RendicionGasto;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\FirmaDisponibleTrait;

class RendicionGastoPolicy
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
        if ($rendicionGasto->procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }

        $firma = $this->isFirmaDisponibleProcesoRendicionActionPolicy($rendicionGasto->procesoRendicionGasto, 'rendicion.actividad.validar');
        if ($rendicionGasto->rinde_gasto && $firma->is_firma) {
            return true;
        }

        return false;
    }

    public function updatemount(User $user, RendicionGasto $rendicionGasto)
    {
        if ($rendicionGasto->procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }

        $firma = $this->isFirmaDisponibleProcesoRendicionActionPolicy($rendicionGasto->procesoRendicionGasto, 'rendicion.actividad.validar');

        $statusProcesoRendicionAll = [
            EstadoProcesoRendicionGasto::STATUS_APROBADO_JD,
            EstadoProcesoRendicionGasto::STATUS_EN_PROCESO,
            EstadoProcesoRendicionGasto::STATUS_VERIFICADO
        ];

        $statusRendicionAll = [
            EstadoRendicionGasto::STATUS_PENDIENTE,
        ];

        if ($rendicionGasto->rinde_gasto && $firma->is_firma && in_array($rendicionGasto->procesoRendicionGasto->status, $statusProcesoRendicionAll) && in_array($rendicionGasto->last_status, $statusRendicionAll)) {
            return true;
        }

        return false;
    }


    public function aprobar(User $user, RendicionGasto $rendicionGasto)
    {
        if ($rendicionGasto->procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }

        $firma = $this->isFirmaDisponibleProcesoRendicionActionPolicy($rendicionGasto->procesoRendicionGasto, 'rendicion.actividad.validar');
        $statusProcesoRendicionAll = [
            EstadoProcesoRendicionGasto::STATUS_APROBADO_JD,
            EstadoProcesoRendicionGasto::STATUS_EN_PROCESO,
            EstadoProcesoRendicionGasto::STATUS_VERIFICADO
        ];
        if ($rendicionGasto->rinde_gasto && $firma->is_firma && in_array($rendicionGasto->procesoRendicionGasto->status, $statusProcesoRendicionAll) && $rendicionGasto->last_status === EstadoRendicionGasto::STATUS_PENDIENTE) {
            return true;
        }

        return false;
    }

    public function rechazar(User $user, RendicionGasto $rendicionGasto)
    {
        if ($rendicionGasto->procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }

        $firma = $this->isFirmaDisponibleProcesoRendicionActionPolicy($rendicionGasto->procesoRendicionGasto, 'rendicion.actividad.validar');
        $statusProcesoRendicionAll = [
            EstadoProcesoRendicionGasto::STATUS_APROBADO_JD,
            EstadoProcesoRendicionGasto::STATUS_EN_PROCESO,
            EstadoProcesoRendicionGasto::STATUS_VERIFICADO
        ];
        if ($rendicionGasto->rinde_gasto && $firma->is_firma && in_array($rendicionGasto->procesoRendicionGasto->status, $statusProcesoRendicionAll) && $rendicionGasto->last_status === EstadoRendicionGasto::STATUS_PENDIENTE) {
            return true;
        }

        return false;
    }

    public function resetear(User $user, RendicionGasto $rendicionGasto)
    {
        if ($rendicionGasto->procesoRendicionGasto->solicitud->status !== Solicitud::STATUS_PROCESADO) {
            return false;
        }

        $firma = $this->isFirmaDisponibleProcesoRendicionActionPolicy($rendicionGasto->procesoRendicionGasto, 'rendicion.actividad.resetear');
        $statusProcesoRendicionAll = [
            EstadoProcesoRendicionGasto::STATUS_APROBADO_JD,
            EstadoProcesoRendicionGasto::STATUS_EN_PROCESO,
            EstadoProcesoRendicionGasto::STATUS_VERIFICADO
        ];
        if (($rendicionGasto->rinde_gasto && $firma->is_firma && in_array($rendicionGasto->procesoRendicionGasto->status, $statusProcesoRendicionAll))  && ($rendicionGasto->last_status === EstadoRendicionGasto::STATUS_APROBADO || $rendicionGasto->last_status === EstadoRendicionGasto::STATUS_RECHAZADO)) {
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
