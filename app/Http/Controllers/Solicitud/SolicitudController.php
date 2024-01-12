<?php

namespace App\Http\Controllers\Solicitud;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\StoreSolicitudRequest;
use App\Http\Requests\Solicitud\UpdateSolicitudRequest;
use App\Http\Requests\Solicitud\ValidateFileSolicitudRequest;
use App\Http\Requests\Solicitud\ValidateInformeSolicitudRequest;
use App\Http\Requests\Solicitud\ValidateInformeUpdateSolicitudRequest;
use App\Http\Resources\Solicitud\UpdateSolicitudResource;
use App\Models\Documento;
use App\Models\Grupo;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class SolicitudController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function storeSolicitud(StoreSolicitudRequest $request)
    {
        try {
            $form = [
                'user_id',
                'fecha_inicio',
                'fecha_termino',
                'hora_llegada',
                'hora_salida',
                'derecho_pago',
                'jornada',
                'dentro_pais',
                'tipo_comision_id',
                'actividad_realizada',
                'gastos_alimentacion',
                'gastos_alojamiento',
                'pernocta_lugar_residencia',
                'n_dias_40',
                'n_dias_100',
                'observacion_gastos'
            ];

            $solicitud = Solicitud::create($request->only($form));

            if ($solicitud) {
                $solicitante = Role::where('name', 'SOLICITANTE')->first();
                $first_firmante[] = [
                    'posicion_firma'    => 0,
                    'status'            => 0,
                    'solicitud_id'      => $solicitud->id,
                    'grupo_id'          => null,
                    'user_id'           => $solicitud->user_id,
                    'role_id'           => $solicitante ? $solicitante->id : null,
                ];

                $estados[] = [
                    'status'                => Solicitud::STATUS_INGRESADA,
                    'posicion_firma'        => 0,
                    'posicion_next_firma'   => 1,
                    'history_solicitud'     => $solicitud,
                    'solicitud_id'          => $solicitud->id,
                    'user_id'               => $solicitud->user_id,
                    'user_firmante_id'      => $solicitud->user_id,
                    'role_firmante_id'      => 6
                ];

                $solicitud->addFirmantes($first_firmante);
                $solicitud->addEstados($estados);

                $grupo = Grupo::where('establecimiento_id', $solicitud->establecimiento_id)
                    ->where('departamento_id', $solicitud->departamento_id)
                    ->where('sub_departamento_id', $solicitud->sub_departamento_id)
                    ->first();

                if (($grupo) && ($grupo->firmantes)) {
                    foreach ($grupo->firmantes as $firmante) {
                        $firmantes_solicitud[] = [
                            'posicion_firma'    => $firmante->posicion_firma,
                            'status'            => $firmante->status,
                            'solicitud_id'      => $solicitud->id,
                            'grupo_id'          => $firmante->grupo_id,
                            'user_id'           => $firmante->user_id,
                            'role_id'           => $firmante->role_id,
                        ];
                    }
                    $solicitud->addFirmantes($firmantes_solicitud);
                }
                if ($request->motivos_cometido) {
                    $solicitud->motivos()->attach($request->motivos_cometido);
                }

                $dentro_pais = (boolean)$request->dentro_pais;

                if (!$dentro_pais) {
                    if ($request->lugares_cometido) {
                        $solicitud->lugares()->attach($request->lugares_cometido);
                    }
                } else {
                    if ($request->paises_cometido) {
                        $solicitud->paises()->attach($request->paises_cometido);
                    }
                }

                if ($request->medio_transporte) {
                    $solicitud->transportes()->attach($request->medio_transporte);
                }

                if ($request->archivos) {
                    foreach ($request->archivos as $file) {

                        $fecha_solicitud    = Carbon::parse($solicitud->fecha_inicio);
                        $year               = $fecha_solicitud->format('Y');
                        $month              = $fecha_solicitud->format('m');
                        $fileName           = 'actividades/' . $solicitud->funcionario->rut . '/' . $year . '/' . $month . '/' . $file->getClientOriginalName();
                        $path               = Storage::disk('public')->putFileAs('archivos', $file, $fileName);

                        $store = Documento::create([
                            'url'           => $path,
                            'nombre'        => $file->getClientOriginalName(),
                            'size'          => $file->getSize(),
                            'format'        => $file->getMimeType(),
                            'extension'     => $file->getClientOriginalExtension(),
                            'is_valid'      => $file->isValid(),
                            'solicitud_id'  => $solicitud->id,
                            'user_id'       => $solicitud->user_id
                        ]);
                    }
                }
                $solicitud = $solicitud->fresh();

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud con código #{$solicitud->codigo} ingresada con éxito.",
                        'message'       => null,
                        'data'          => $solicitud
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateSolicitud(UpdateSolicitudRequest $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();

            $form = [
                'fecha_inicio',
                'fecha_termino',
                'hora_llegada',
                'hora_salida',
                'derecho_pago',
                'jornada',
                'dentro_pais',
                'tipo_comision_id',
                'actividad_realizada',
                'gastos_alimentacion',
                'gastos_alojamiento',
                'pernocta_lugar_residencia',
                'n_dias_40',
                'n_dias_100',
                'observacion_gastos'
            ];
            $update = $solicitud->update($request->only($form));

            if ($update) {
                if ($request->motivos_cometido) {
                    $solicitud->motivos()->sync($request->motivos_cometido);
                }

                $dentro_pais = (boolean)$request->dentro_pais;
                if (!$dentro_pais) {
                    if ($request->lugares_cometido) {
                        $solicitud->lugares()->sync($request->lugares_cometido);
                    }

                    if ($solicitud->paises) {
                        $solicitud->paises()->detach();
                    }
                } else {
                    if ($request->paises_cometido) {
                        $solicitud->paises()->sync($request->paises_cometido);
                    }

                    if ($solicitud->lugares) {
                        $solicitud->lugares()->detach();
                    }
                }

                if ($request->medio_transporte) {
                    $solicitud->transportes()->sync($request->medio_transporte);
                }

                $solicitud              = $solicitud->fresh();
                $last_estado_solicitud  = $solicitud->estados()->orderBy('id', 'DESC')->first();

                if ($last_estado_solicitud) {
                    $posicion_a_firmar = $last_estado_solicitud->posicion_next_firma;
                    $auth_user         = Auth::user();

                    $last_firma_disponible_user_auth  = $solicitud->firmantes()
                        ->where('user_id', $auth_user->id)
                        ->where('posicion_firma', '<=', $posicion_a_firmar)
                        ->orderBy('posicion_firma', 'DESC')
                        ->first();

                    $next_firma_disponible  = $solicitud->firmantes()
                        ->where('posicion_firma', $last_firma_disponible_user_auth->posicion_firma + 1)
                        ->orderBy('posicion_firma', 'ASC')
                        ->first();

                    $estados[] = [
                        'status'                => Solicitud::STATUS_MODIFICADA,
                        'posicion_firma'        => $last_firma_disponible_user_auth ? $last_firma_disponible_user_auth->posicion_firma : null,
                        'posicion_next_firma'   => $next_firma_disponible ? $next_firma_disponible->posicion_firma : null,
                        'history_solicitud'     => $solicitud,
                        'solicitud_id'          => $solicitud->id,
                        'user_id'               => $last_firma_disponible_user_auth->user_id,
                        'role_id'               => $last_firma_disponible_user_auth->role_id,
                        'user_firmante_id'      => $next_firma_disponible ? $next_firma_disponible->user_id : null,
                        'role_firmante_id'      => $next_firma_disponible ? $next_firma_disponible->role_id : null
                    ];
                    $create_status  = $solicitud->addEstados($estados);
                }

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud con código #{$solicitud->codigo} modificada con éxito.",
                        'message'       => null,
                        'data'          => null
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
    public function getSolicitud($uuid)
    {
        try {
            $solicitud = Solicitud::where('uuid', $uuid)->firstOrFail();
            /* if (($solicitud) && ($solicitud->last_status === 4)) {
                return response()->json([
                    'errors' => [
                        'solicitud'  => "Solicitud anulada."
                    ]
                ], 422);
            } */

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => UpdateSolicitudResource::make($solicitud)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function validateSolicitud(ValidateInformeSolicitudRequest $request)
    {
        try {
            $validate_date              = $this->validateSolicitudDate($request);
            $validate_date_derecho_pago = $this->validateSolicitudDateDerechoViatico($request);
            $validate_date_year         = $this->validateDateYear($request->fecha_inicio, $request->fecha_termino);

            if ($validate_date) {
                $message = "Ya existe una solicitud en la fecha seleccionada.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

            if ($validate_date_derecho_pago) {
                $message = "Ya existe una solicitud con derecho a viático en la fecha seleccionada.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

            if (!$validate_date_year) {
                $message = "Fechas de cometido deben ser en un mismo año.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

            return response()->json(
                array(
                    'status'        => 'success',
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function validateUpdateSolicitud(ValidateInformeUpdateSolicitudRequest $request)
    {
        try {
            $validate_date              = $this->validateUpdateSolicitudDate($request);
            $validate_date_derecho_pago = $this->validateSolicitudUpdateDateDerechoViatico($request);
            $validate_date_year         = $this->validateDateYear($request->fecha_inicio, $request->fecha_termino);

            if ($validate_date) {
                $message = "Ya existe una solicitud en la fecha seleccionada.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

            if ($validate_date_derecho_pago) {
                $message = "Ya existe una solicitud con derecho a viático en la fecha seleccionada.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

            if (!$validate_date_year) {
                $message = "Fechas de cometido deben ser en un mismo año.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

            return response()->json(
                array(
                    'status'        => 'success',
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateSolicitudDate($request)
    {
        try {
            $existe         = false;
            $fecha_inicio   = $request->fecha_inicio;
            $fecha_termino  = $request->fecha_termino;

            $hora_llegada  = $request->hora_llegada;
            $hora_salida   = $request->hora_salida;
            $solicitudes = Solicitud::where('user_id', $request->user_id)
                ->whereIn('last_status', [0, 1])
                ->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                    $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_inicio)
                            ->where('fecha_termino', '>=', $fecha_inicio);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_termino)
                            ->where('fecha_termino', '>=', $fecha_termino);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '>=', $fecha_inicio)
                            ->where('fecha_termino', '<=', $fecha_termino);
                    });
                })
                ->where(function ($query) use ($hora_llegada, $hora_salida) {
                    $query->where(function ($query) use ($hora_llegada, $hora_salida) {
                        $query->where('hora_llegada', '<=', $hora_llegada)
                            ->where('hora_salida', '>=', $hora_llegada);
                    })->orWhere(function ($query) use ($hora_llegada, $hora_salida) {
                        $query->where('hora_llegada', '<=', $hora_salida)
                            ->where('hora_salida', '>=', $hora_salida);
                    })->orWhere(function ($query) use ($hora_llegada, $hora_salida) {
                        $query->where('hora_llegada', '>=', $hora_llegada)
                            ->where('hora_salida', '<=', $hora_salida);
                    });
                })
                ->count();

            if ($solicitudes > 0) {
                $existe = true;
            }
            return $existe;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateDateYear($fecha_inicio, $fecha_termino)
    {
        $anio_inicio   = (int)Carbon::parse($fecha_inicio)->format('Y');
        $anio_termino  = (int)Carbon::parse($fecha_termino)->format('Y');

        if ($anio_inicio !== $anio_termino) {
            return false;
        }
        return true;
    }

    private function validateSolicitudDateDerechoViatico($request)
    {
        try {
            $existe         = false;
            $fecha_inicio   = $request->fecha_inicio;
            $fecha_termino  = $request->fecha_termino;

            $hora_llegada  = $request->hora_llegada;
            $hora_salida   = $request->hora_salida;
            $derecho_pago  = (bool)$request->derecho_pago;
            $solicitudes = 0;

            if ($derecho_pago) {
                $solicitudes = Solicitud::where('user_id', $request->user_id)
                    ->whereIn('last_status', [0, 1])
                    ->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '<=', $fecha_inicio)
                                ->where('fecha_termino', '>=', $fecha_inicio);
                        })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '<=', $fecha_termino)
                                ->where('fecha_termino', '>=', $fecha_termino);
                        })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '>=', $fecha_inicio)
                                ->where('fecha_termino', '<=', $fecha_termino);
                        });
                    })
                    ->where('derecho_pago', true)
                    ->count();
            }



            if ($solicitudes > 0) {
                $existe = true;
            }
            return $existe;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateSolicitudUpdateDateDerechoViatico($request)
    {
        try {
            $existe         = false;
            $fecha_inicio   = $request->fecha_inicio;
            $fecha_termino  = $request->fecha_termino;

            $hora_llegada   = $request->hora_llegada;
            $hora_salida    = $request->hora_salida;
            $derecho_pago   = (bool)$request->derecho_pago;
            $solicitudes    = 0;
            $solicitud      = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();

            if ($derecho_pago) {
                $solicitudes = Solicitud::where('id', '!=', $solicitud->id)
                    ->where('user_id', $solicitud->user_id)
                    ->whereIn('last_status', [0, 1])
                    ->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '<=', $fecha_inicio)
                                ->where('fecha_termino', '>=', $fecha_inicio);
                        })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '<=', $fecha_termino)
                                ->where('fecha_termino', '>=', $fecha_termino);
                        })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '>=', $fecha_inicio)
                                ->where('fecha_termino', '<=', $fecha_termino);
                        });
                    })
                    ->where('derecho_pago', true)
                    ->count();
            }



            if ($solicitudes > 0) {
                $existe = true;
            }
            return $existe;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateUpdateSolicitudDate($request)
    {
        try {
            $existe         = false;
            $fecha_inicio   = $request->fecha_inicio;
            $fecha_termino  = $request->fecha_termino;

            $hora_llegada  = $request->hora_llegada;
            $hora_salida   = $request->hora_salida;
            $solicitud     = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $solicitudes  = Solicitud::where('id', '!=', $solicitud->id)
                ->where('user_id', $solicitud->user_id)
                ->whereIn('last_status', [0, 1, 2, 3])
                ->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                    $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_inicio)
                            ->where('fecha_termino', '>=', $fecha_inicio);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_termino)
                            ->where('fecha_termino', '>=', $fecha_termino);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '>=', $fecha_inicio)
                            ->where('fecha_termino', '<=', $fecha_termino);
                    });
                })
                ->where(function ($query) use ($hora_llegada, $hora_salida) {
                    $query->where(function ($query) use ($hora_llegada, $hora_salida) {
                        $query->where('hora_llegada', '<=', $hora_llegada)
                            ->where('hora_salida', '>=', $hora_llegada);
                    })->orWhere(function ($query) use ($hora_llegada, $hora_salida) {
                        $query->where('hora_llegada', '<=', $hora_salida)
                            ->where('hora_salida', '>=', $hora_salida);
                    })->orWhere(function ($query) use ($hora_llegada, $hora_salida) {
                        $query->where('hora_llegada', '>=', $hora_llegada)
                            ->where('hora_salida', '<=', $hora_salida);
                    });
                })
                ->count();

            if ($solicitudes > 0) {
                $existe = true;
            }
            return $existe;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function datesSolicitudInCalendar(Request $request)
    {
        try {
            $fecha_inicio       = Carbon::parse($request->fecha_inicio)->format('Y-m-d');
            $fecha_termino      = Carbon::parse($request->fecha_termino)->format('Y-m-d');
            $funcionario        = User::find($request->user_id);
            $derecho_pago       = (bool)$request->derecho_pago;
            $dates              = [];
            if ($derecho_pago) {
                $solicitudes = Solicitud::where('user_id', $funcionario->id)
                    ->where('fecha_inicio', '>=', $fecha_inicio)
                    ->where('fecha_termino', '<=', $fecha_termino)
                    ->where('derecho_pago', true)
                    ->get();

                if (count($solicitudes) > 0) {
                    foreach ($solicitudes as $solicitud) {
                        $fecha_inicio_solicitud   = Carbon::parse($solicitud->fecha_inicio)->format('Y-m-d');
                        $fecha_termino_solicitud  = Carbon::parse($solicitud->fecha_termino)->format('Y-m-d');

                        for ($i = $fecha_inicio_solicitud; $i <= $fecha_termino_solicitud; $i++) {
                            $date       = Carbon::parse($i)->format('Y-m-d');
                            $dates[]    = $date;
                        }
                    }
                }
            }

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => $dates
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
