<?php

namespace App\Http\Controllers\Admin\Rendicion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rendicion\StatusRendicionRequest;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoResource;
use App\Http\Resources\Rendicion\RendicionGastoResource;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoRendicionGasto;
use App\Models\ProcesoRendicionGasto;
use App\Models\RendicionGasto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\FirmaDisponibleTrait;

class ProcesoRendicionController extends Controller
{
    use FirmaDisponibleTrait;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getProcesoRendiciones()
    {
        try {
            $proceso_rendiciones = ProcesoRendicionGasto::all();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ProcesoRendicionGastoResource::collection($proceso_rendiciones)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getProcesoRendicion($uuid)
    {
        try {
            $rendicion = ProcesoRendicionGasto::where('uuid', $uuid)->firstOrFail();
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ProcesoRendicionGastoDetalleResource::make($rendicion)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function statusRendicion(StatusRendicionRequest $request, $uuid)
    {
        try {
            $rendicion      = RendicionGasto::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $rendicion);
            $rendicion_old  = $rendicion->replicate();
            $status         = (int)$request->status;
            $message_status = $status === 1 ? 'aprobada' : 'rechazada';

            if ($rendicion) {
                $mount_real = (int)$request->mount_real;
                $update     = $rendicion->update([
                    'last_status'   => $status,
                    'mount_real'    => $status === 0 ? $rendicion->mount : $mount_real
                ]);

                if ($update) {
                    $firma_disponible = $this->obtenerFirmaDisponibleRendicion($rendicion->procesoRendicionGasto);
                    $count_rendiciones              = $rendicion->procesoRendicionGasto->rendiciones()->where('rinde_gasto', true)->count();
                    $count_rendiciones_aprobadas    = $rendicion->procesoRendicionGasto->rendiciones()->where('rinde_gasto', true)->where('last_status', '!=', EstadoRendicionGasto::STATUS_PENDIENTE)->count();
                    if ($count_rendiciones_aprobadas >= $count_rendiciones) {
                        $estado = [
                            'status'                => EstadoProcesoRendicionGasto::STATUS_VERIFICADO,
                            'p_rendicion_gasto_id'  => $rendicion->procesoRendicionGasto->id,
                            'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                            'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null
                        ];
                        $status_r = EstadoProcesoRendicionGasto::create($estado);
                    } else {
                        $total_en_proceso = $rendicion->procesoRendicionGasto->estados()->where('status', EstadoProcesoRendicionGasto::STATUS_EN_PROCESO)->count();

                        if ($total_en_proceso <= 0) {
                            $estado = [
                                'status'                => EstadoProcesoRendicionGasto::STATUS_EN_PROCESO,
                                'p_rendicion_gasto_id'  => $rendicion->procesoRendicionGasto->id,
                                'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                                'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null
                            ];
                            $status_r = EstadoProcesoRendicionGasto::create($estado);
                        }
                    }
                    $rendicion  = $rendicion->fresh();
                    $data[]     = [
                        'status'          => $status,
                        'observacion'     => $request->observacion,
                        'mount_real'      => $mount_real,
                        'rendicion_old'   => $rendicion_old,
                        'rendicion_new'   => $rendicion
                    ];

                    $rendicion->addStatus($data);
                    $status_proceso_rendicion = EstadoProcesoRendicionGasto::STATUS_NOM[$rendicion->procesoRendicionGasto->status];
                    return response()->json(
                        array(
                            'status'  => 'success',
                            'title'   => "Rendición {$message_status} con éxito",
                            'message' => "Proceso de rendición $status_proceso_rendicion",
                            'data'    => ProcesoRendicionGastoDetalleResource::make($rendicion->procesoRendicionGasto)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updatePago(Request $request)
    {
        try {
            $proceso_rendicion_gasto = ProcesoRendicionGasto::where('uuid', $request->uuid)->firstOrFail();

            if ($proceso_rendicion_gasto) {
                $update = $proceso_rendicion_gasto->update([
                    'dias_habiles_pago'    => $request->dias_habiles_pago != null ? (int)$request->dias_habiles_pago : NULL
                ]);

                if ($update) {
                    $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();
                    return response()->json(
                        array(
                            'status'  => 'success',
                            'title'   => "Rendición $proceso_rendicion_gasto->n_folio.",
                            'message' => "Días de pago modificado con éxito",
                            'data'    => ProcesoRendicionGastoDetalleResource::make($proceso_rendicion_gasto)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
