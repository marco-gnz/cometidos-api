<?php

namespace App\Http\Controllers\Admin\Solicitudes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\StatusSolicitudRequest;
use App\Http\Resources\Escala\ListEscalaResource;
use App\Http\Resources\Grupo\ListFirmantesResource;
use App\Http\Resources\ListSolicitudCalculoAdminResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Solicitud\ListActividadesResource;
use App\Http\Resources\Solicitud\ListCalculoResoruce;
use App\Http\Resources\Solicitud\ListConvenioResource;
use App\Http\Resources\Solicitud\ListSolicitudAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudCalculoPropuestaAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudCompleteAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use App\Http\Resources\Solicitud\ListSolicitudStatusResource;
use App\Http\Resources\Solicitud\PropuestaCalculoSolicitud;
use App\Http\Resources\Solicitud\StatusSolicitudResource;
use App\Models\Convenio;
use App\Models\Escala;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\SoliucitudCalculo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SebastianBergmann\Type\FalseType;
use Spatie\Permission\Models\Role;

class SolicitudAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listSolicitudes()
    {
        try {
            $solicitudes = Solicitud::orderBy('fecha_inicio', 'DESC')->get();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ListSolicitudAdminResource::collection($solicitudes)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function findSolicitud($uuid, $nav)
    {
        try {
            $solicitud                      = Solicitud::where('uuid', $uuid)->withCount('documentos')->firstOrFail();
            $navStatus                      = $this->navStatusSolicitud($solicitud);
            $permisos_solicitud             = $this->permissionsSolicitud($solicitud);

            switch ($nav) {
                case 'datos':
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'nav'           => StatusSolicitudResource::collection($navStatus)
                        )
                    );
                    break;

                case 'firmantes':
                    $firmantes = $solicitud->firmantes()->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListFirmantesResource::collection($firmantes),
                            'nav'           => StatusSolicitudResource::collection($navStatus)
                        )
                    );
                    break;

                case 'calculo':
                    $calculo = $solicitud->calculos()->orderBy('id', 'DESC')->first();

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => $calculo ? ListCalculoResoruce::make($calculo) : null,
                            'nav'           => StatusSolicitudResource::collection($navStatus)
                        )
                    );

                    break;

                case 'convenio':
                    $convenio = $solicitud->convenio;
                    $convenios = $this->getConvenios($solicitud);

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => $convenio ? ListConvenioResource::make($convenio) : null,
                            'nav'           => StatusSolicitudResource::collection($navStatus),
                            'convenios'     => $convenios ? ListConvenioResource::collection($convenios) : null
                        )
                    );

                    break;

                case 'rendiciones':
                    $rendiciones = $solicitud->procesoRendicionGastos()->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ProcesoRendicionGastoDetalleResource::collection($rendiciones),
                            'nav'           => StatusSolicitudResource::collection($navStatus)
                        )
                    );
                    break;

                case 'archivos':
                    $documentos = $solicitud->documentos()->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListSolicitudDocumentosResource::collection($documentos),
                            'nav'           => StatusSolicitudResource::collection($navStatus)
                        )
                    );
                    break;

                case 'seguimiento':
                    $estados = $solicitud->estados()->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListSolicitudStatusResource::collection($estados),
                            'nav'           => StatusSolicitudResource::collection($navStatus)
                        )
                    );
                    break;
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateConvenio(Request $request)
    {
        try {
            $corresponde = (int)$request->corresponde;
            $solicitud   = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            switch ($corresponde) {
                case 1:
                    $convenio                   = Convenio::where('id', $request->convenio_id)->where('active', true)->firstOrFail();
                    $count_solicitudes_convenio = $this->validateNConvenios($convenio, $solicitud);

                    if ($count_solicitudes_convenio >= $convenio->n_viatico_mensual) {
                        return $this->errorResponse("No es posible asociar convenio. Convenio admite {$convenio->n_viatico_mensual} cometidos al mes y este registra un total de {$count_solicitudes_convenio}.", 422);
                    }
                    $update     = $solicitud->update([
                        'convenio_id'       => $convenio->id,
                        'afecta_convenio'   => true
                    ]);

                    $message = "Solicitud de cometido afecta a convenio {$convenio->codigo}";
                    break;
                case 0:
                    $update = $solicitud->update([
                        'convenio_id'       => null,
                        'afecta_convenio'   => false
                    ]);

                    $message = 'Solicitud de cometido no afecta a un convenio.';
                    break;
            }

            if ($update) {
                $navStatus = $this->navStatusSolicitud($solicitud);
                $solicitud = $solicitud->fresh();

                $convenio  = $solicitud->convenio;
                $convenios = $this->getConvenios($solicitud);

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud modifcada con éxito.",
                        'message'       => $message,
                        'data'          => $convenio ? ListConvenioResource::make($convenio) : null,
                        'nav'           => StatusSolicitudResource::collection($navStatus),
                        'convenios'     => $convenios ? ListConvenioResource::collection($convenios) : null
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateNConvenios($convenio, $solicitud)
    {
        try {
            $month = Carbon::parse($solicitud->fecha_inicio)->format('m');
            $year  = Carbon::parse($solicitud->fecha_inicio)->format('Y');
            $total_solicitudes = Solicitud::where('convenio_id', $convenio->id)
                ->whereMonth('fecha_inicio', $month)
                ->whereYear('fecha_inicio', $year)
                ->where('last_status', '!=', 4)
                ->count();

            return $total_solicitudes;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function getConvenios($solicitud)
    {
        try {
            $fecha_inicio       = $solicitud->fecha_inicio;
            $fecha_termino      = $solicitud->fecha_termino;
            $funcionario        = $solicitud->funcionario;
            $convenios    = Convenio::where('user_id', $funcionario->id)
                ->where('active', true)
                ->where('estamento_id', $funcionario->estamento_id)
                ->where('ley_id', $funcionario->ley_id)
                ->where('establecimiento_id', $funcionario->establecimiento_id)
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
                ->get();

            return $convenios;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    /* private function navStatusSolicitud($solicitud)
    {
        $new_firmantes          = [];
        $firmas                 = $solicitud->firmantes()->orderBy('posicion_firma', 'ASC')->get();
        $total_pendiente        = 0;
        $total_reasignado       = 0;
        $last_estado_solicitud  = $solicitud->estados()->orderBy('id', 'DESC')->first();

        foreach ($firmas as $firma) {
            $last_estado = $solicitud->estados()
                ->where('user_firmante_id', $firma->user_id)
                ->where('role_firmante_id', $firma->role_id)
                ->orderBy('id', 'DESC')
                ->first();

            $status = $this->getStatus($last_estado, $total_pendiente, $total_reasignado, $last_estado_solicitud);

            $fir = (object)[
                'user_uuid'             => $firma->funcionario->uuid,
                'nombres_firmante'      => $firma->funcionario->nombre_completo,
                'posicion_firma'        => $firma->posicion_firma,
                'perfil'                => $firma->perfil->name,
                'is_firma'              => $status['is_firma'],
                'status_nom'            => $status['status_nom'],
                'status_value'          => $status['status_value'],
                'status_date'           => $status['status_date'],
                'reasignacion'          => $status['reasignacion'],
                'reasignar_firma_value' => $status['reasignar_firma_value'],
                'type'                  => $status['type'],
                'total_pendiente'       => $status['total_pendiente'],
                'is_actual'             => $status['is_actual']
            ];

            array_push($new_firmantes, $fir);
        }
        Log::info($new_firmantes);
        return $new_firmantes;
    } */

    private function permissionsSolicitud($solicitud)
    {
        $permissions = [];

        if ($solicitud->last_status !== 4) {
            array_push($permissions, 'solicitud.edit');
        }

        return $permissions;
    }

    private function getStatus($last_estado, &$total_pendiente, &$total_reasignado, $last_estado_solicitud)
    {
        $status = [
            'is_firma'              => false,
            'status_nom'            => null,
            'status_value'          => null,
            'status_date'           => null,
            'reasignacion'          => false,
            'reasignar_firma_value' => true,
            'type'                  => '#808080',
            'total_pendiente'       => $total_pendiente,
            'is_actual'             => false,
        ];

        if ($last_estado) {
            $status['is_firma']     = $last_estado->status !== null;
            $status['status_nom']   = $last_estado ? Solicitud::STATUS_NOM[$last_estado->status] : null;
            $status['status_value'] = $last_estado ? $last_estado->status : null;
            $status['status_date']  = $last_estado ? Carbon::parse($last_estado->created_at)->format('d-m-Y H:i') : null;
            $status['reasignacion'] = $last_estado->reasignacion;

            if ($last_estado->status === 1) {
                $total_pendiente++;
            }

            if ($last_estado->reasignacion) {
                $total_reasignado++;
            }
        }

        $status['reasignar_firma_value']    = $this->getReasignarFirmaValue($last_estado, $total_reasignado);
        $status['type']                     = $this->getType($last_estado, $total_pendiente);
        $status['is_actual']                = ($last_estado && $this->isActual($last_estado, $last_estado_solicitud)) ? true : false;

        return $status;
    }

    private function getReasignarFirmaValue($last_estado, $total_reasignado)
    {
        $reasignar_firma_value = true;
        if (!$last_estado) {
            $reasignar_firma_value = false;
        } else if (($last_estado) && ($last_estado->status === 1)) {
            $reasignar_firma_value = false;
        } else if ($total_reasignado > 0) {
            $reasignar_firma_value = false;
        } else if (($last_estado) && ($last_estado->reasignacion)) {
            $reasignar_firma_value = false;
        }
        return $reasignar_firma_value;
    }

    private function getType($last_estado, $total_pendiente)
    {
        $type = '#808080';

        if ($last_estado) {
            switch ($last_estado->status) {
                case 1:
                    $type = '#0e6db8';
                    break;
                case 0:
                case 2:
                    if ($total_pendiente <= 0) {
                        $type = '#7ac143';
                    }
                    break;
                case 3:
                case 4:
                    $type = '#dc3545';
                    break;
            }
        }

        return $type;
    }

    private function isActual($last_estado, $last_estado_solicitud)
    {
        return ($last_estado) && ($last_estado_solicitud && $last_estado->id === $last_estado_solicitud->id);
    }

    private function navStatusSolicitud($solicitud)
    {


        $new_firmantes          = [];
        $firmas                 = $solicitud->firmantes()->orderBy('posicion_firma', 'ASC')->get();
        $total_pendiente        = 0;
        $total_reasignado       = 0;
        $last_estado_solicitud  = $solicitud->estados()->orderBy('id', 'DESC')->first();
        foreach ($firmas as $firma) {
            $last_estado = $solicitud->estados()->where('user_firmante_id', $firma->user_id)->where('role_firmante_id', $firma->role_id)->orderBy('id', 'DESC')->first();
            if ($last_estado) {
                if ($last_estado->status === 1) {
                    $total_pendiente++;
                }
                if ($last_estado->reasignacion) {
                    $total_reasignado++;
                }
            }

            $reasignar_firma_value = true;
            if (!$last_estado) {
                $reasignar_firma_value = false;
            } else if (($last_estado) && ($last_estado->status === 1)) {
                $reasignar_firma_value = false;
            } else if ($total_reasignado > 0) {
                $reasignar_firma_value = false;
            } else if (($last_estado) && ($last_estado->reasignacion)) {
                $reasignar_firma_value = false;
            }

            $type = '#808080';
            if ($last_estado) {
                switch ($last_estado->status) {
                    case 1:
                        $type = '#0e6db8';
                        break;

                    case 0:
                    case 2:
                        if ($total_pendiente <= 0) {
                            $type = '#7ac143';
                        }
                        break;

                    case 3:
                    case 4:
                        $type = '#dc3545';
                        break;
                }
            }
            $is_actual      = ($last_estado && $last_estado_solicitud) && ($last_estado->id === $last_estado_solicitud->id) ? true : false;
            $reasignacion   = ($last_estado) && ($is_actual && $last_estado->reasignacion) ? true : false;
            if ($total_pendiente > 0) {
                $fir = (object) [
                    'user_uuid'                 => $firma->funcionario->uuid,
                    'nombres_firmante'          => $firma->funcionario->nombre_completo,
                    'posicion_firma'            => $firma->posicion_firma,
                    'perfil'                    => $firma->perfil->name,
                    'is_firma'                  => ($last_estado) && ($last_estado->status != null) ? true : false,
                    'status_nom'                => Solicitud::STATUS_NOM[1],
                    'status_value'              => null,
                    'status_date'               => null,
                    'reasignacion'              => $reasignacion,
                    'reasignar_firma_value'     => $reasignar_firma_value,
                    'type'                      => '#808080',
                    'total_pendiente'           => $total_pendiente,
                    'is_actual'                 => $is_actual
                ];
            } else {
                $fir = (object) [
                    'user_uuid'                 => $firma->funcionario->uuid,
                    'nombres_firmante'          => $firma->funcionario->nombre_completo,
                    'posicion_firma'            => $firma->posicion_firma,
                    'perfil'                    => $firma->perfil->name,
                    'is_firma'                  => ($last_estado) && ($last_estado->status) != null ? true : false,
                    'status_nom'                => $last_estado ? Solicitud::STATUS_NOM[$last_estado->status] : Solicitud::STATUS_NOM[1],
                    'status_value'              => $last_estado ? $last_estado->status : null,
                    'status_date'               => $last_estado ? Carbon::parse($last_estado->created_at)->format('d-m-Y H:i') : null,
                    'reasignacion'              => $reasignacion,
                    'reasignar_firma_value'     => $reasignar_firma_value,
                    'type'                      => $type,
                    'total_pendiente'           => $total_pendiente,
                    'is_actual'                 => $is_actual
                ];
            }

            array_push($new_firmantes, $fir);
        }
        return $new_firmantes;
    }

    public function reasignarFirmaSolicitud(Request $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            if (($solicitud) && ($solicitud->last_status === 4)) {
                return $this->errorResponse("No es posible ejecutar firma. Solicitud anulada.", 422);
            }
            $funcionario_firmante   = User::where('uuid', $request->user_uuid)->firstOrFail();
            $firmante               = $solicitud->firmantes()->where('user_id', $funcionario_firmante->id)->where('posicion_firma', $request->posicion_firma)->firstOrFail();
            $auth_user              = Auth::user();
            $estados[] = [
                'status'                    => 1,
                'posicion_firma'            => null,
                'posicion_next_firma'       => $firmante->posicion_firma,
                'reasignacion'              => true,
                'history_solicitud'         => $solicitud,
                'solicitud_id'              => $solicitud->id,
                'user_id'                   => $auth_user ? $auth_user->id : null,
                'role_id'                   => null,
                'user_firmante_id'          => $firmante->user_id,
                'role_firmante_id'          => $firmante->role_id
            ];

            $create_status  = $solicitud->addEstados($estados);
            $solicitud      = $solicitud->fresh();
            $navStatus      = $this->navStatusSolicitud($solicitud);

            if ($create_status) {
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud {$solicitud->codigo} reasignada.",
                        'message'       => "Firma reasgignada a {$funcionario_firmante->nombre_completo}",
                        'data'          => ListSolicitudStatusResource::make($solicitud),
                        'nav'           => StatusSolicitudResource::collection($navStatus)
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function propuestaCalculo(Request $request)
    {
        try {
            $new_escalas    = [];
            $solicitud      = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $fecha_inicio   = $solicitud->fecha_inicio;
            $fecha_termino  = $solicitud->fecha_termino;
            $is_escala      = false;

            $escala_solicitud = $this->buscarEscalaSolicitud($solicitud, $fecha_inicio, $fecha_termino);

            $escalas = $this->buscarEscalas($fecha_inicio, $fecha_termino, $solicitud);

            $new_escalas = $escalas->map(function ($escala) use ($escala_solicitud) {
                $escala->{'is_selected'} = ($escala_solicitud && $escala->id === $escala_solicitud->id);
                return $escala;
            });

            $this->asignarMontos($solicitud, $escala_solicitud);

            $solicitud->{'is_escala'} = $escala_solicitud ? true : false;

            return response()->json([
                'status'    => 'success',
                'title'     => null,
                'message'   => null,
                'data'      => PropuestaCalculoSolicitud::make($solicitud),
                'escalas'   => ListEscalaResource::collection($new_escalas),
            ]);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function buscarEscalaSolicitud($solicitud, $fecha_inicio, $fecha_termino)
    {
        $escala = null;
        $ley_solicitud = $solicitud->ley->nombre;

        switch ($ley_solicitud) {
            case '15.076':
            case '19.664':
                $escala = Escala::where(function ($query) use ($fecha_inicio, $fecha_termino) {
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
                    ->where('ley_id', $solicitud->ley_id)
                    ->first();
                break;

            default:
                $escala = Escala::where(function ($query) use ($fecha_inicio, $fecha_termino) {
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
                    ->where('ley_id', $solicitud->ley_id)
                    ->where('grado_id', $solicitud->grado_id)
                    ->first();
                break;
        }

        return $escala;
    }

    private function buscarEscalas($fecha_inicio, $fecha_termino, $solicitud)
    {
        return Escala::where(function ($query) use ($fecha_inicio, $fecha_termino) {
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
            ->with('grado')
            ->get()
            ->sortBy('grado.nombre');
    }

    private function asignarMontos($solicitud, $escala_solicitud)
    {
        if ($escala_solicitud) {
            $monto_40 = $solicitud->n_dias_40 ? $escala_solicitud->valor_dia_40 * $solicitud->n_dias_40 : null;
            $monto_100 = $solicitud->n_dias_100 ? $escala_solicitud->valor_dia_100 * $solicitud->n_dias_100 : null;

            $solicitud->{'monto_40'}    = $monto_40;
            $solicitud->{'monto_100'}   = $monto_100;
            $solicitud->{'monto_total'} = $monto_40 + $monto_100;
        }
    }

    public function aplicarCalculo($uuid)
    {
        try {
            $solicitud = Solicitud::where('uuid', $uuid)->firstOrFail();

            if ($this->solicitudAnulada($solicitud)) {
                return $this->errorResponse("No es posible ejecutar acción. Solicitud anulada.", 422);
            }

            $escala = $this->buscarEscalaSolicitud($solicitud, $solicitud->fecha_inicio, $solicitud->fecha_termino);

            if (!$escala) {
                return $this->errorResponse("No existe escala de valores.", 422);
            }

            $data_calculo   = $this->crearDataCalculo($solicitud, $escala);
            if ($this->existeCalculoIdentico($data_calculo)) {
                return $this->errorResponse("Ya existe un cálculo idéntico.", 422);
            }
            $new_calculo    = SoliucitudCalculo::create($data_calculo);

            if ($new_calculo) {
                $this->actualizarSolicitud($solicitud);

                return response()->json([
                    'status'    => 'success',
                    'title'     => "Cálculo aplicado correctamente.",
                    'message'   => null,
                    'data'      => ListCalculoResoruce::make($new_calculo),
                ]);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function existeCalculoIdentico($data_calculo)
    {
        return SoliucitudCalculo::where($data_calculo)->exists();
    }

    private function actualizarSolicitud($solicitud)
    {
        /* $solicitud->update(['calculo_aplicado' => true]); */
        $solicitud = $solicitud->fresh();

        $solicitud = $solicitud->withCount('calculos')->with(['calculos' => function ($q) {
            $q->orderBy('id', 'DESC')->first();
        }])->first();
    }

    private function crearDataCalculo($solicitud, $escala)
    {
        $monto_40   = $solicitud->n_dias_40 ? $escala->valor_dia_40 * $solicitud->n_dias_40 : null;
        $monto_100  = $solicitud->n_dias_100 ? $escala->valor_dia_100 * $solicitud->n_dias_100 : null;

        return [
            'fecha_inicio'  => $escala->fecha_inicio,
            'fecha_termino' => $escala->fecha_termino,
            'n_dias_100'    => $solicitud->n_dias_100,
            'n_dias_40'     => $solicitud->n_dias_40,
            'valor_dia_40'  => $escala->valor_dia_40,
            'valor_dia_100' => $escala->valor_dia_100,
            'monto_40'      => $monto_40,
            'monto_100'     => $monto_100,
            'solicitud_id'  => $solicitud->id,
            'ley_id'        => $escala->ley_id,
            'grado_id'      => $escala->grado_id,
        ];
    }

    private function errorResponse($message, $statusCode)
    {
        return response()->json([
            'errors' => [
                'solicitud' => $message,
            ],
        ], $statusCode);
    }

    private function solicitudAnulada($solicitud)
    {
        return $solicitud && $solicitud->last_status === 4;
    }

    public function checkActionFirma(Request $request)
    {
        try {
            $status                     = (int)$request->status;
            $solicitud                  = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $last_estado_solicitud      = $solicitud->estados()->orderBy('id', 'DESC')->first();
            $posicion_a_firmar          = $last_estado_solicitud->posicion_next_firma;

            $auth_user         = Auth::user();
            $firma_disponible  = $solicitud->firmantes()
                ->where('posicion_firma', $posicion_a_firmar)
                ->orderBy('posicion_firma', 'ASC')
                ->first();

            $is_firma = true;
            $title    = null;
            $message  = null;
            if ($firma_disponible->user_id !== $auth_user->id) {
                $is_firma = false;
                $title      = 'No es posible aplicar verificación.';
                $message    = "{$firma_disponible->funcionario->nombre_completo} es quien debe ejecutar dicha verificación.";
            }

            if ($status === 4) {
                $is_firma = true;
            }
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => $title,
                    'message'       => $message,
                    'is_firma'      => $is_firma,
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function actionStatusSolicitud(StatusSolicitudRequest $request)
    {
        try {
            //realizar un switch case dependiendo el status
            //si status = 4 no se debe aplicar validación de existencia de firma
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            if (($solicitud) && ($solicitud->last_status === 4)) {
                return $this->errorResponse("No es posible ejecutar firma. Solicitud anulada.", 422);
            }
            $last_estado_solicitud  = $solicitud->estados()->orderBy('id', 'DESC')->first();
            $status                 = (int)$request->status;
            $posicion_a_firmar      = 0;
            $auth_user              = Auth::user();
            switch ($status) {
                case 1:
                    $funcionario_firmante   = User::where('uuid', $request->user_uuid)->firstOrFail();
                    $firmante               = $solicitud->firmantes()->where('user_id', $funcionario_firmante->id)->where('posicion_firma', $request->posicion_firma)->firstOrFail();

                    $estados[] = [
                        'status'                    => 1,
                        'posicion_firma'            => null,
                        'posicion_next_firma'       => $firmante->posicion_firma,
                        'reasignacion'              => true,
                        'history_solicitud'         => $solicitud,
                        'solicitud_id'              => $solicitud->id,
                        'user_id'                   => $auth_user ? $auth_user->id : null,
                        'role_id'                   => null,
                        'user_firmante_id'          => $firmante->user_id,
                        'role_firmante_id'          => $firmante->role_id,
                        'observacion'               => $request->observacion,
                    ];
                    $create_status  = $solicitud->addEstados($estados);
                    $solicitud      = $solicitud->fresh();
                    $navStatus      = $this->navStatusSolicitud($solicitud);
                    if ($create_status) {
                        return response()->json(
                            array(
                                'status'        => 'success',
                                'title'         => "Solicitud {$solicitud->codigo} reasignada.",
                                'message'       => "Firma reasgignada a {$funcionario_firmante->nombre_completo}",
                                'data'          => null,
                                'nav'           => StatusSolicitudResource::collection($navStatus)
                            )
                        );
                    }
                    break;

                default:
                    if ($last_estado_solicitud) {
                        $posicion_a_firmar = $last_estado_solicitud->posicion_next_firma;
                        $firma_disponible  = $solicitud->firmantes()
                            ->where('user_id', $auth_user->id)
                            ->where('posicion_firma', $posicion_a_firmar)
                            ->orderBy('posicion_firma', 'ASC')
                            ->first();



                        if (!$firma_disponible) {
                            return $this->errorResponse("No existe firma disponible.", 422);
                        }

                        if ($firma_disponible) {
                            if (is_null($solicitud->afecta_convenio) && ($firma_disponible->role_id === 1 || $firma_disponible->role_id === 5 || $firma_disponible->role_id === 7)) {
                                return $this->errorResponse("No es posible ejecutar firma. EJECUTIVO RRHH debe verificar si solicitud de cometido está afecta o no a un convenio.", 422);
                            }
                        }

                        $estados[] = [
                            'status'                => $status,
                            'motivo_rechazo'        => $status === 3 ? $request->motivo_id : NULL,
                            'posicion_firma'        => $firma_disponible->posicion_firma,
                            'posicion_next_firma'   => $status === 3 ? 0 : $firma_disponible->posicion_firma + 1,
                            'history_solicitud'     => $solicitud,
                            'solicitud_id'          => $solicitud->id,
                            'user_id'               => $auth_user ? $auth_user->id : null,
                            'role_id'               => $firma_disponible ?  $firma_disponible->role_id : null,
                            'user_firmante_id'      => $auth_user ? $auth_user->id : null,
                            'role_firmante_id'      => $firma_disponible ? $firma_disponible->role_id : null,
                            'observacion'           => $request->observacion,
                        ];

                        if ($status === 3) {
                            $solicitante = Role::where('name', 'SOLICITANTE')->first();
                            $estados[] = [
                                'status'                => 1,
                                'motivo_rechazo'        => NULL,
                                'posicion_firma'        => $firma_disponible->posicion_firma,
                                'posicion_next_firma'   => 0,
                                'history_solicitud'     => $solicitud,
                                'solicitud_id'          => $solicitud->id,
                                'user_id'               => $auth_user ? $auth_user->id : null,
                                'role_id'               => $firma_disponible ?  $firma_disponible->role_id : null,
                                'user_firmante_id'      => $solicitud ? $solicitud->user_id : NULL,
                                'role_firmante_id'      => $solicitante ? $solicitante->id : NULL,
                                'reasignacion'          => true,
                            ];
                        }

                        $create_status  = $solicitud->addEstados($estados);
                        $solicitud      = $solicitud->fresh();
                        $navStatus      = $this->navStatusSolicitud($solicitud);

                        return response()->json(
                            array(
                                'status'        => 'success',
                                'title'         => "Solicitud {$solicitud->codigo} modificada.",
                                'message'       => null,
                                'data'          => null,
                                'nav'           => StatusSolicitudResource::collection($navStatus)
                            )
                        );
                    } else {
                        return $this->errorResponse("No existe firma disponible.", 422);
                    }
                    break;
            }
            /* if ($last_estado_solicitud) {
                $auth_user              = Auth::user();
                $posicion_a_firmar      = $last_estado_solicitud->posicion_next_firma;

                $firma_disponible     = $solicitud->firmantes()
                    ->where('user_id', $auth_user->id)
                    ->where('posicion_firma', $posicion_a_firmar)
                    ->orderBy('posicion_firma', 'ASC')
                    ->first();

                if (!$firma_disponible) {
                    return response()->json([
                        'errors' => [
                            'solicitud'  => "No existe firma disponible."
                        ]
                    ], 422);
                }

                $estados[] = [
                    'status'                => $status,
                    'motivo_rechazo'        => $status === 3 ? $request->motivo_id : NULL,
                    'posicion_firma'        => $firma_disponible->posicion_firma,
                    'posicion_next_firma'   => $status === 3 ? 0 : $firma_disponible->posicion_firma + 1,
                    'history_solicitud'     => $solicitud,
                    'solicitud_id'          => $solicitud->id,
                    'user_id'               => $auth_user ? $auth_user->id : null,
                    'role_id'               => $firma_disponible ?  $firma_disponible->role_id : null,
                    'user_firmante_id'      => $auth_user ? $auth_user->id : null,
                    'role_firmante_id'      => $firma_disponible ? $firma_disponible->role_id : null,
                    'observacion'           => $request->observacion,
                ];

                if ($status === 3) {
                    $solicitante = Role::where('name', 'SOLICITANTE')->first();
                    $estados[] = [
                        'status'                => 1,
                        'motivo_rechazo'        => NULL,
                        'posicion_firma'        => $firma_disponible->posicion_firma,
                        'posicion_next_firma'   => 0,
                        'history_solicitud'     => $solicitud,
                        'solicitud_id'          => $solicitud->id,
                        'user_id'               => $auth_user ? $auth_user->id : null,
                        'role_id'               => $firma_disponible ?  $firma_disponible->role_id : null,
                        'user_firmante_id'      => $solicitud ? $solicitud->user_id : NULL,
                        'role_firmante_id'      => $solicitante ? $solicitante->id : NULL,
                        'reasignacion'          => true,
                    ];
                }

                $create_status  = $solicitud->addEstados($estados);
                $solicitud      = $solicitud->fresh();
                $navStatus      = $this->navStatusSolicitud($solicitud);

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud {$solicitud->codigo} modificada.",
                        'message'       => null,
                        'data'          => null,
                        'nav'           => StatusSolicitudResource::collection($navStatus)
                    )
                );
            } else {
                return response()->json([
                    'errors' => [
                        'solicitud'  => ["No existe firma disponible."]
                    ]
                ], 422);
            } */
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
