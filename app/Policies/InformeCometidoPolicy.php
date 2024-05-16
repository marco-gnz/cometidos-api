<?php

namespace App\Policies;

use App\Models\EstadoInformeCometido;
use App\Models\InformeCometido;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\FirmaDisponibleTrait;

class InformeCometidoPolicy
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
     * @param  \App\Models\InformeCometido  $informeCometido
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, InformeCometido $informeCometido)
    {
        if ($informeCometido->solicitud->status === Solicitud::STATUS_ANULADO || $informeCometido->last_status !== EstadoInformeCometido::STATUS_APROBADO) {
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, InformeCometido $informeCometido, Solicitud $solicitud)
    {
        $informe = $solicitud->informeCometido();
        if (!$informe) {
            if ($solicitud->status != Solicitud::STATUS_ANULADO) {
                $fecha = "$solicitud->fecha_termino $solicitud->hora_salida";
                $fecha_termino_solicitud    = Carbon::parse($fecha);
                $now                        = Carbon::now();

                if ($fecha_termino_solicitud->lte($now) && $user->is_informe) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        return false;
    }

    public function createother(User $user)
    {
        if (!$user->is_informe) {
            return false;
        }

        return true;
    }

    public function aprobar(User $user, InformeCometido $informeCometido)
    {
        $status = [
            EstadoInformeCometido::STATUS_INGRESADA,
            EstadoInformeCometido::STATUS_MODIFICADO
        ];

        if ($informeCometido->solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

        $firma = $this->isFirmaDisponibleActionPolicy($informeCometido->solicitud, 'solicitud.informes.validar');
        if ($firma->is_firma && in_array($informeCometido->last_status, $status)) {
            return true;
        }

        return false;
    }

    public function rechazar(User $user, InformeCometido $informeCometido)
    {
        $status = [
            EstadoInformeCometido::STATUS_INGRESADA,
            EstadoInformeCometido::STATUS_MODIFICADO,
            EstadoInformeCometido::STATUS_APROBADO
        ];

        if ($informeCometido->solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

        $firma = $this->isFirmaDisponibleActionPolicy($informeCometido->solicitud, 'solicitud.informes.validar');
        if ($firma->is_firma && in_array($informeCometido->last_status, $status)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\InformeCometido  $informeCometido
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, InformeCometido $informeCometido)
    {
        $status = [
            EstadoInformeCometido::STATUS_INGRESADA,
            EstadoInformeCometido::STATUS_MODIFICADO
        ];
        if ($informeCometido->user_id_by === $user->id && in_array($informeCometido->last_status, $status)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\InformeCometido  $informeCometido
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, InformeCometido $informeCometido)
    {
        $status = [
            EstadoInformeCometido::STATUS_INGRESADA,
            EstadoInformeCometido::STATUS_MODIFICADO
        ];
        if ($informeCometido->user_id_by === $user->id && in_array($informeCometido->last_status, $status)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\InformeCometido  $informeCometido
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, InformeCometido $informeCometido)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\InformeCometido  $informeCometido
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, InformeCometido $informeCometido)
    {
        //
    }
}
