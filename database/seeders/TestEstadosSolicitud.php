<?php

namespace Database\Seeders;

use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Faker\Factory;
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
            $faker      = Factory::create();
            $solicitud  = Solicitud::first();

            if ($solicitud) {
                $first_firma = $solicitud->firmantes()->where('posicion_firma', 0)->where('status', true)->first();

                $status = 0;
                $estado = new EstadoSolicitud();
                $estado->status             = $status;
                $estado->posicion_firma_s   = $first_firma ? $first_firma->posicion_firma : null;
                $estado->solicitud_id       = $solicitud->id;
                $estado->user_id            = $first_firma ? $first_firma->user_id : null;
                $estado->s_role_id          = 1;
                $estado->s_firmante_id      = $first_firma ? $first_firma->id : null;
                $estado->save();

                $firmantes = $solicitud->firmantes()->where('posicion_firma', '>', 0)->where('status', true)->get();
                foreach ($firmantes as $key => $firma) {
                    $posicion_firma = $firma->posicion_firma;
                    switch ($posicion_firma) {
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                            $status = 2;
                            $estado = new EstadoSolicitud();
                            $estado->status             = $status;
                            $estado->posicion_firma_s   = $firma->posicion_firma;
                            $estado->solicitud_id       = $solicitud->id;
                            $estado->user_id            = $firma->user_id;
                            $estado->s_firmante_id      = $firma->id;
                            $estado->save();
                            break;

                            /* case 4:
                            $status = 3;
                            $estado = new EstadoSolicitud();
                            $estado->status             = $status;
                            $estado->posicion_firma_s   = $firma->posicion_firma;
                            $estado->solicitud_id       = $solicitud->id;
                            $estado->user_id            = $firma->user_id;
                            $estado->s_firmante_id      = $firma->id;
                            if ($status === 3) {
                                $estado->motivo_rechazo     = EstadoSolicitud::RECHAZO_3;
                                $estado->observacion        = $faker->text();
                            }
                            $estado->save(); */

                            /* if ($status === 3) {
                                $solicitud              = $solicitud->fresh();
                                $firmante_reasignado    = $solicitud->firmantes()->where('posicion_firma', '>', 0)->where('posicion_firma', '<', $solicitud->posicion_firma_actual)->where('status', true)->first();
                                if ($firmante_reasignado) {
                                    $status = 1;
                                    $estado = new EstadoSolicitud();
                                    $estado->status             = $status;
                                    $estado->posicion_firma_s   = $firma->posicion_firma;
                                    $estado->solicitud_id       = $solicitud->id;
                                    $estado->user_id            = $firma->user_id;
                                    $estado->s_firmante_id      = $firma->id;
                                    $estado->is_reasignado      = true;
                                    $estado->r_s_firmante_id    = $firmante_reasignado->id;
                                    $estado->posicion_firma_r_s = $firmante_reasignado->posicion_firma;
                                    $estado->observacion        = $faker->text();
                                    $estado->save();

                                    $firmante_reasignado->update([
                                        'is_reasignado' =>  true
                                    ]);
                                }
                            } */

                            /* $firmante_reasignado = $solicitud->firmantes()->where('posicion_firma', 2)->where('status', true)->first();
                            if ($firmante_reasignado) {
                                $status = 1;
                                $estado = new EstadoSolicitud();
                                $estado->status             = $status;
                                $estado->posicion_firma_s   = $firma->posicion_firma;
                                $estado->solicitud_id       = $solicitud->id;
                                $estado->user_id            = $firma->user_id;
                                $estado->s_firmante_id      = $firma->id;
                                $estado->is_reasignado      = true;
                                $estado->r_s_firmante_id    = $firmante_reasignado->id;
                                $estado->posicion_firma_r_s = $firmante_reasignado->posicion_firma;
                                $estado->observacion        = "Reasignada por falta de antecedentes y se requiere modificar.";
                                $estado->save();

                                $firmante_reasignado->update([
                                    'is_reasignado' =>  true
                                ]);
                            } */

                            break;
                    }
                }
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }
}
