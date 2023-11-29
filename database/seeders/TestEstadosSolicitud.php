<?php

namespace Database\Seeders;

use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class TestEstadosSolicitud extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $solicitud = Solicitud::first();

            if ($solicitud) {
                EstadoSolicitud::truncate();
                $estado = new EstadoSolicitud();
                $estado->status = 0;
                $estado->history_solicitud = $solicitud;
                $estado->solicitud_id = $solicitud->id;
                $estado->user_id = $solicitud->user_id;
                $estado->save();

                $firmantes = $solicitud->grupo->firmantes()->get();

                foreach ($firmantes as $key => $firmante) {
                    switch ($key) {
                        case 0:
                        case 1:
                        case 2:
                            $status = 2;
                            $estado = new EstadoSolicitud();
                            $estado->status = $status;
                            $estado->history_solicitud = $solicitud;
                            $estado->solicitud_id = $solicitud->id;
                            $estado->user_id = $firmante->user_id;
                            $estado->role_id = $firmante->role_id;
                            $estado->user_firmante_id = $firmante->user_id;
                            $estado->role_firmante_id = $firmante->role_id;
                            $estado->save();
                            break;

                        case 3:
                            $jefe_directo = $solicitud->grupo->firmantes()->where('role_id', 3)->first();
                            $status = 1;
                            $estado                     = new EstadoSolicitud();
                            $estado->status             = $status;
                            $estado->history_solicitud  = $solicitud;
                            $estado->solicitud_id       = $solicitud->id;
                            $estado->user_id            = $firmante->user_id;
                            $estado->role_id            = $firmante->role_id;
                            $estado->reasignacion       = true;
                            $estado->user_firmante_id   = $jefe_directo->user_id;
                            $estado->role_firmante_id   = $jefe_directo->role_id;
                            $estado->save();

                            /* $status = 3;
                            $estado                     = new EstadoSolicitud();
                            $estado->status             = $status;
                            $estado->history_solicitud  = $solicitud;
                            $estado->solicitud_id       = $solicitud->id;
                            $estado->user_id            = $jefe_directo->user_id;
                            $estado->role_id            = $jefe_directo->role_id;
                            $estado->reasignado         = true;
                            $estado->save(); */
                            break;
                    }
                }
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }
}
