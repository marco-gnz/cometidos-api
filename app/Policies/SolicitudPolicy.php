<?php

namespace App\Policies;

use App\Models\Configuration;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\FirmaDisponibleTrait;

class SolicitudPolicy
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
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Solicitud $solicitud)
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
        $now = Carbon::now()->format('Y-m-d');
        $total_informes_pendientes = (int)Configuration::obtenerValor('informecometido.total_pendiente');
        $total_por_ingresar_informes = $user->solicitudes()
            ->where('status', '!=', Solicitud::STATUS_ANULADO)
            ->where('fecha_termino', '<', $now)
            ->whereDoesntHave('informes')
            ->count();

        if ($total_por_ingresar_informes > $total_informes_pendientes) {
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

        $last_status = $solicitud->estados()->orderBy('id', 'DESC')->first();
        if (($last_status) && ($last_status->status === EstadoSolicitud::STATUS_INGRESADA || $last_status->status === EstadoSolicitud::STATUS_MODIFICADA || $last_status->status === EstadoSolicitud::STATUS_RECHAZADO && $solicitud->posicion_firma_actual === 0 || $last_status->status === EstadoSolicitud::STATUS_PENDIENTE && $last_status->posicion_firma === 0 &&  $last_status->is_reasignado && $solicitud->posicion_firma_actual === 0)) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */

    public function firma(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

        $firma = $this->obtenerFirmaDisponible($solicitud);

        if ($firma->is_firma && $solicitud->status === Solicitud::STATUS_EN_PROCESO) {
            return true;
        }

        return false;
    }

    public function reasignaremergency(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO || $solicitud->status === Solicitud::STATUS_EN_PROCESO) {
            return false;
        }
        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR')) {
            return true;
        }

        return false;
    }

    public function anular(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

        $firma = $this->obtenerFirmaDisponibleSolicitudAnular($solicitud);

        if ($firma->is_firma || $user->hasRole('SUPER ADMINISTRADOR')) {
            return true;
        }

        return false;
    }

    public function sincronizargrupo(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

        if (!$solicitud->grupo && $user->hasRole('SUPER ADMINISTRADOR')) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Solicitud $solicitud)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Solicitud $solicitud)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Solicitud $solicitud)
    {
        //
    }
}