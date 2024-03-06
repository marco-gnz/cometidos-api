<?php

namespace App\Policies;

use App\Models\Configuration;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;

class SolicitudPolicy
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
        $last_status = $solicitud->estados()->orderBy('id', 'DESC')->first();

        if (($last_status) && ($last_status->status === EstadoSolicitud::STATUS_INGRESADA || $last_status->status === EstadoSolicitud::STATUS_PENDIENTE)) {
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
