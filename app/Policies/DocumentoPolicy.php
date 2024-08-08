<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\ProcesoRendicionGasto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentoPolicy
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
     * @param  \App\Models\Documento  $documento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Documento $documento)
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
     * @param  \App\Models\Documento  $documento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Documento $documento)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Documento  $documento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Documento $documento)
    {
        if (($user->id === $documento->user_id) && ($documento->model === Documento::MODEL_SOLICITUD) && ($documento->solicitud->last_status === EstadoSolicitud::STATUS_INGRESADA || $documento->solicitud->last_status === EstadoSolicitud::STATUS_MODIFICADA)) {
            return true;
        }

        if (($user->id === $documento->user_id) && ($documento->model === Documento::MODEL_RENDICION) && ($documento->procesoRendicionGasto) && ($documento->procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_INGRESADA || $documento->procesoRendicionGasto->status === EstadoProcesoRendicionGasto::STATUS_MODIFICADA)) {
            return true;
        }

        return false;
    }



    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Documento  $documento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Documento $documento)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Documento  $documento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Documento $documento)
    {
        //
    }
}
