<?php

namespace App\Http\Controllers\Admin\Rendicion;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoResource;
use App\Http\Resources\Rendicion\RendicionGastoResource;
use App\Models\ProcesoRendicionGasto;
use App\Models\RendicionGasto;
use Illuminate\Http\Request;

class ProcesoRendicionController extends Controller
{
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

    public function statusRendicion(Request $request, $uuid)
    {
        try {
            $rendicion      = RendicionGasto::where('uuid', $uuid)->firstOrFail();
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
                    $rendicion  = $rendicion->fresh();
                    $data[]     = [
                        'status'          => $status,
                        'observacion'     => $request->observacion,
                        'mount_real'      => $mount_real,
                        'rendicion_old'   => $rendicion_old,
                        'rendicion_new'   => $rendicion
                    ];

                    $rendicion->addStatus($data);
                    return response()->json(
                        array(
                            'status'  => 'success',
                            'title'   => "Rendición {$message_status} con éxito",
                            'message' => null,
                            'data'    => ProcesoRendicionGastoDetalleResource::make($rendicion->procesoRendicionGasto)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
