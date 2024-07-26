<?php

namespace App\Http\Controllers\Solicitud;

use App\Events\InformeCometidoCreated;
use App\Events\InformeCometidoStatus;
use App\Events\SolicitudCreated;
use App\Events\SolicitudUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Informe\StatusInformeRequest;
use App\Http\Requests\Informe\StoreInformeRequest;
use App\Http\Requests\Informe\UpdateInformeRequest;
use App\Http\Requests\Solicitud\StoreSolicitudRequest;
use App\Http\Requests\Solicitud\UpdateSolicitudRequest;
use App\Http\Requests\Solicitud\ValidateFileSolicitudRequest;
use App\Http\Requests\Solicitud\ValidateInformeSolicitudRequest;
use App\Http\Requests\Solicitud\ValidateInformeUpdateSolicitudRequest;
use App\Http\Resources\Solicitud\InformeCometidoUpdateResource;
use App\Http\Resources\Solicitud\ListInformeCometidoAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudCompleteAdminResource;
use App\Http\Resources\Solicitud\UpdateSolicitudResource;
use App\Models\CicloFirma;
use App\Models\Concepto;
use App\Models\ConceptoEstablecimiento;
use App\Models\Configuration;
use App\Models\Contrato;
use App\Models\Convenio;
use App\Models\Documento;
use App\Models\EstadoInformeCometido;
use App\Models\EstadoSolicitud;
use App\Models\Grupo;
use App\Models\InformeCometido;
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
use App\Traits\FirmaDisponibleTrait;
use App\Traits\StatusSolicitudTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SolicitudController extends Controller
{
    use FirmaDisponibleTrait, StatusSolicitudTrait;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getCountConvenios(Request $request)
    {
        try {
            $fecha_inicio       = $request->fecha_inicio;
            $fecha_termino      = $request->fecha_termino;
            $contrato        = Contrato::where('uuid', $request->contrato_uuid)->firstOrFail();

            $total_convenios    = $contrato->funcionario->convenios()
                ->where('active', true)
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
                ->count();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => $total_convenios
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function feriados($fecha)
    {
        $fecha      = Carbon::parse($fecha);
        $anio       = $fecha->format('Y');
        $cacheKey   = "feriados_{$anio}";
        $feriados   = Cache::get($cacheKey);
        if ($feriados !== null) {
            return $feriados;
        }

        try {
            $url        = "https://apis.digital.gob.cl/fl/feriados/{$anio}";
            $response   = Http::get($url);
            if ($response->successful()) {
                $apiResponse = $response->body();
                $feriados = json_decode($apiResponse, true, 512, JSON_UNESCAPED_UNICODE);

                if (is_array($feriados)) {
                    $fechas = collect($feriados)->pluck('fecha')->toArray();
                    Cache::put($cacheKey, $fechas, now()->addDays(31));
                    return $fechas;
                }
            }
            return [];
        } catch (\Exception $exception) {
            Log::error("Error al procesar la solicitud de feriados: {$exception->getMessage()}");
            $feriados = Cache::get($cacheKey);
            return $feriados !== null ? $feriados : [];
        }
    }

    private function getWeekendCount(Carbon $startDate, Carbon $endDate): int
    {
        $weekendCount = 0;

        while ($startDate->lte($endDate)) {
            if ($startDate->isSaturday() || $startDate->isSunday()) {
                $weekendCount++;
            }
            $startDate->addDay();
        }

        return $weekendCount;
    }

    private function getFeriadosCount(Carbon $startDate, Carbon $endDate): int
    {
        $feriadosCount          = 0;
        $array_fechas_feriados  = $this->feriados($startDate);
        while ($startDate->lte($endDate)) {
            if ((!$startDate->isSaturday() && !$startDate->isSunday()) && in_array($startDate->format('Y-m-d'), $array_fechas_feriados)) {
                $feriadosCount++;
            }
            $startDate->addDay();
        }

        return $feriadosCount;
    }

    public function isPlazoAvion(Request $request)
    {
        try {
            $request->validate([
                'contrato_uuid' => 'required|exists:contratos,uuid',
                'fecha_inicio'  => 'required|date',
            ]);

            $contrato           = Contrato::where('uuid', $request->contrato_uuid)->firstOrFail();
            $diaz_plazo_avion   = (int)Configuration::obtenerValor('solicitud.dias_plazo_avion', $contrato->establecimiento_id);
            $now                = Carbon::now();
            $fecha_inicio       = Carbon::parse($request->fecha_inicio);
            $status = null;
            $title = null;
            $message = null;
            if ($fecha_inicio->format('Y-m-d') >= $now->format('Y-m-d')) {
                $diff_in_days           = $now->diffInDays($fecha_inicio) + 1;
                $fecha_termino_f        = $fecha_inicio->copy();
                $fds                    = $this->getWeekendCount(Carbon::now(), $fecha_termino_f);
                $feriados               = $this->getFeriadosCount(Carbon::now(), $fecha_termino_f);
                $total_descuento        = $fds + $feriados;
                $diff_in_days_total     = $diff_in_days - $total_descuento;
                $status             = 'success';
                $title              = "Plazo de Avión está dentro del plazo de {$diaz_plazo_avion} días hábiles.";
                $message            = null;
                if ($diff_in_days_total < $diaz_plazo_avion) {
                    $status     = 'error';
                    $title      = "Plazo de Avión está fuera del plazo de {$diaz_plazo_avion} días hábiles.";
                    $message    = 'Solicitud puede ser rechazada y debe indicar el motivo en la observación por el cuál está fuera de plazo';
                }
            }

            return response()->json(
                array(
                    'status'        => $status,
                    'title'         => $title,
                    'message'       => $message,
                    'data'          => null
                )
            );
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeSolicitud(StoreSolicitudRequest $request)
    {
        try {
            DB::beginTransaction();
            $validate_date              = $this->validateSolicitudDate($request);
            $validate_days_40_100       = $this->validateSolicitudDate40100($request);
            $validate_date_derecho_pago = $this->validateSolicitudDateDerechoViatico($request);
            $validate_date_year         = $this->validateDateYear($request->fecha_inicio, $request->fecha_termino);

            if (!$validate_date_year) {
                $message = "Fechas de cometido deben ser en un mismo año.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

            if (!$validate_days_40_100) {
                $message = "Total de días al 40 y 100 no coinciden con diferencia de fecha solicitada.";
                return response()->json([
                    'errors' => [
                        'n_dias_40'  => [$message],
                        'n_dias_100' => [$message]
                    ]
                ], 422);
            }

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


            $contrato = Contrato::where('uuid', $request->contrato_uuid)->firstOrFail();

            $data = [
                'user_id'                   => $request->user_id,
                'fecha_inicio'              => $request->fecha_inicio,
                'fecha_termino'             => $request->fecha_termino,
                'hora_llegada'              => $request->hora_llegada,
                'hora_salida'               => $request->hora_salida,
                'derecho_pago'              => $request->derecho_pago,
                'utiliza_transporte'        => $request->utiliza_transporte,
                'viaja_acompaniante'        => $request->viaja_acompaniante,
                'alimentacion_red'          => $request->alimentacion_red,
                'jornada'                   => $request->jornada,
                'dentro_pais'               => $request->dentro_pais,
                'tipo_comision_id'          => $request->tipo_comision_id,
                'actividad_realizada'       => $request->actividad_realizada,
                'gastos_alimentacion'       => $request->gastos_alimentacion,
                'gastos_alojamiento'        => $request->gastos_alojamiento,
                'pernocta_lugar_residencia' => $request->pernocta_lugar_residencia,
                'n_dias_40'                 => $request->n_dias_40,
                'n_dias_100'                => $request->n_dias_100,
                'observacion_gastos'        => $request->observacion_gastos,
                'departamento_id'           => $contrato->departamento_id,
                'sub_departamento_id'       => $contrato->sub_departamento_id,
                'ley_id'                    => $contrato->ley_id,
                'calidad_id'                => $contrato->calidad_id,
                'cargo_id'                  => $contrato->cargo_id,
                'grado_id'                  => $contrato->grado_id,
                'estamento_id'              => $contrato->estamento_id,
                'establecimiento_id'        => $contrato->establecimiento_id,
                'hora_id'                   => $contrato->hora_id,
                'grupo_id'                  => $contrato->grupo_id,
                'observacion'               => $request->observacion
            ];

            $solicitud = Solicitud::create($data);
            if ($solicitud) {
                $solicitante = Role::where('name', 'SOLICITANTE')->first();
                $first_firmante[] = [
                    'posicion_firma'    => 0,
                    'solicitud_id'      => $solicitud->id,
                    'grupo_id'          => null,
                    'user_id'           => $solicitud->user_id,
                    'role_id'           => $solicitante ? $solicitante->id : null,
                    'permissions_id'    => $this->getPermissions($solicitante->id, $solicitud)
                ];

                $solicitud->addFirmantes($first_firmante);
                $solicitud = $solicitud->fresh();

                $firma = $solicitud->firmantes()->where('posicion_firma', 0)->first();
                $estados[] = [
                    'status'                => EstadoSolicitud::STATUS_INGRESADA,
                    'posicion_firma_s'      => $firma ? $firma->posicion_firma : 0,
                    'history_solicitud_new' => json_encode($solicitud->only($data)),
                    'solicitud_id'          => $solicitud->id,
                    'user_id'               => $firma ? $firma->user_id : null,
                    's_role_id'             => $firma ? $firma->role_id : null,
                    's_firmante_id'         => $firma ? $firma->id : null,
                ];
                $solicitud->addEstados($estados);

                if ($solicitud->grupo) {
                    $firmantes = $solicitud->grupo->firmantes()->where('status', true)->orderBy('posicion_firma', 'ASC')->get();
                    if ($firmantes) {
                        $posicion_firma = 1;
                        foreach ($firmantes as $firmante) {
                            $status = true;
                            if ($firmante->role_id === 6 || $firmante->role_id === 7) {
                                $status = true;
                                if (!$solicitud->derecho_pago) {
                                    $status = false;
                                }
                            }
                            $firmantes_solicitud[] = [
                                'posicion_firma'    => $posicion_firma++,
                                'status'            => $firmante->status,
                                'solicitud_id'      => $solicitud->id,
                                'grupo_id'          => $firmante->grupo_id,
                                'user_id'           => $firmante->user_id,
                                'role_id'           => $firmante->role_id,
                                'status'            => $status,
                                'permissions_id'    => $this->getPermissions($firmante->role_id, $solicitud)
                            ];
                        }

                        if ($solicitud->tipo_comision_id === 5) {
                            $conceptoEstablecimiento = ConceptoEstablecimiento::where('establecimiento_id', $solicitud->establecimiento_id)
                                ->whereHas('concepto', function ($q) {
                                    $q->where('nombre', 'CAPACITACIÓN FINANCIAMIENTO CENTRALIZADO');
                                })->first();
                            if ($conceptoEstablecimiento) {
                                $fecha_by_solicitud = Carbon::parse($solicitud->fecha_by_user)->format('Y-m-d');
                                $first_user = $conceptoEstablecimiento->funcionarios()
                                    ->first();

                                if ($first_user) {
                                    $posicion_actual    = 1;
                                    $nuevos_firmantes   = [];
                                    foreach ($firmantes_solicitud as $firmante) {
                                        $nuevos_firmantes[] = $firmante;
                                        $id_permission_valorizacion_crear   = $this->idPermission('solicitud.valorizacion.crear');
                                        if ($firmante['role_id'] === 2) {
                                            // Crear el nuevo firmante de capacitación
                                            $firmante_capacitacion = [
                                                'posicion_firma'    => $firmante['posicion_firma'] + 1,
                                                'solicitud_id'      => $solicitud->id,
                                                'grupo_id'          => $solicitud->grupo_id,
                                                'user_id'           => $first_user->id,
                                                'role_id'           => 10,
                                                'status'            => true,
                                                'permissions_id'    => $this->getPermissions(10, $solicitud)
                                            ];
                                            $nuevos_firmantes[] = $firmante_capacitacion;
                                            $posicion_actual++;
                                        } else {
                                            if (in_array($id_permission_valorizacion_crear, $firmante['permissions_id'])) {
                                                $firmante_capacitacion = [
                                                    'posicion_firma'    => $firmante['posicion_firma'] + 1,
                                                    'solicitud_id'      => $solicitud->id,
                                                    'grupo_id'          => $solicitud->grupo_id,
                                                    'user_id'           => $first_user->id,
                                                    'role_id'           => 10,
                                                    'status'            => true,
                                                    'permissions_id'    => $this->getPermissions(10, $solicitud)
                                                ];
                                                $nuevos_firmantes[] = $firmante_capacitacion;
                                                $posicion_actual++;
                                            }
                                        }
                                        $nuevos_firmantes[count($nuevos_firmantes) - 1]['posicion_firma'] = $posicion_actual;
                                        $posicion_actual++;
                                    }
                                    $firmantes_solicitud = $nuevos_firmantes;
                                }
                            }
                        }
                        $solicitud->addFirmantes($firmantes_solicitud);
                    }
                }
                if ($request->motivos_cometido) {
                    $solicitud->motivos()->attach($request->motivos_cometido);
                }

                $dentro_pais = (bool)$request->dentro_pais;

                if (!$dentro_pais) {
                    if ($request->lugares_cometido) {
                        $solicitud->lugares()->attach($request->lugares_cometido);
                    }
                } else {
                    if ($request->paises_cometido) {
                        $solicitud->paises()->attach($request->paises_cometido);
                    }
                }
                $utiliza_transporte = (bool)$request->utiliza_transporte;
                if ($utiliza_transporte === true) {
                    if ($request->medio_transporte) {
                        $solicitud->transportes()->attach($request->medio_transporte);
                    }
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
                            'user_id'       => $solicitud->user_id,
                            'model'         => Documento::MODEL_SOLICITUD
                        ]);
                    }
                }
                $solicitud = $solicitud->fresh();

                $fecha = "$solicitud->fecha_termino $solicitud->hora_salida";
                $fecha_termino_solicitud = Carbon::parse($fecha);
                $now                     = Carbon::now();
                $uuid                    = null;
                if ($fecha_termino_solicitud->lte($now)) {
                    $message = "Su Cometido fue realizado. ¿Requiere ingresar su Informe de Cometido inmediatamente?";
                    $uuid    = $solicitud->uuid;
                } else {
                    $message = "¿Requiere ingresar una nueva solicitud de cometido?";
                }

                if ($solicitud) {
                    $emails_copy = [];
                    if ($solicitud->tipo_comision_id === 5) {
                        $name = 'CAPACITACIÓN FINANCIAMIENTO CENTRALIZADO';
                        $concepto = Concepto::where('nombre', $name)->first();
                        if ($concepto) {
                            $conceptoEstablecimiento = $concepto->conceptosEstablecimientos()
                                ->where('establecimiento_id', $solicitud->establecimiento_id)
                                ->first();

                            $emails_copy = $conceptoEstablecimiento->funcionarios()->pluck('users.email')->toArray();
                        }
                    }
                    SolicitudCreated::dispatch($solicitud, $emails_copy);
                }
                DB::commit();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud con código {$solicitud->codigo} ingresada con éxito.",
                        'message'       => $message,
                        'data'          => $uuid
                    )
                );
            }
        } catch (\Exception $error) {
            DB::rollback();
            Log::info($error->getMessage());
            return response()->json($error->getMessage(), 500);
        }
    }

    private function solicitudInformeEstados($solicitud)
    {
        $informes = $solicitud->informes()->whereIn('last_status', [EstadoInformeCometido::STATUS_INGRESADA, EstadoInformeCometido::STATUS_APROBADO])->count();

        if ($informes > 0) {
            return false;
        }
        return true;
    }

    public function deleteInformeCometido($uuid)
    {
        try {
            DB::beginTransaction();
            $informe = InformeCometido::where('uuid', $uuid)->firstOrFail();
            $this->authorize('delete', $informe);
            $informe->transportes()->detach();
            $delete = $informe->delete();

            if ($delete) {
                DB::commit();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Informe de cometido eliminado con éxito.",
                        'message'       => null,
                        'data'          => null
                    )
                );
            }
        } catch (\Exception $error) {
            DB::rollback();
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getInformeCometidoUpdate($uuid)
    {
        try {
            $informe = InformeCometido::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $informe);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => InformeCometidoUpdateResource::make($informe)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeInformeCometido(StoreInformeRequest $request)
    {
        try {
            $form = [
                'fecha_inicio',
                'fecha_termino',
                'hora_llegada',
                'hora_salida',
                'utiliza_transporte',
                'dentro_pais',
                'medio_transporte',
                'actividad_realizada'
            ];
            $solicitud = Solicitud::where('uuid', $request->uuid_solicitud)->firstOrFail();
            $this->authorize('create', [new InformeCometido, $solicitud]);
            $store_informes_pendientes = $this->solicitudInformeEstados($solicitud);

            if (!$store_informes_pendientes) {
                return response()->json([
                    'errors' => [
                        'otros'  => 'No es posible ingresar informe. Ya existen informes de cometidos pendientes o aprobados.'
                    ]
                ], 422);
            }
            $informeCometido = new InformeCometido($request->only($form));

            // Asigna la solicitud al informe cometido
            $informeCometido->solicitud()->associate($solicitud);

            // Guarda el nuevo informe cometido
            $informeCometido->save();

            if ($informeCometido) {
                $utiliza_transporte = (bool)$request->utiliza_transporte;
                if ($utiliza_transporte === true) {
                    if ($request->medio_transporte) {
                        $informeCometido->transportes()->attach($request->medio_transporte);
                    }
                }

                $estados[] = [
                    'status'                    => EstadoInformeCometido::STATUS_INGRESADA,
                    'informe_cometido_id'       => $informeCometido->id
                ];

                $create_status      = $informeCometido->addEstados($estados);
                $informeCometido    = $informeCometido->fresh();

                InformeCometidoCreated::dispatch($informeCometido);
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Informe de cometido {$informeCometido->codigo} ingresado con éxito.",
                        'message'       => null,
                        'data'          => null
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateInformeCometido($uuid, UpdateInformeRequest $request)
    {
        try {
            DB::beginTransaction();
            $informeCometido = InformeCometido::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $informeCometido);
            $form = [
                'fecha_inicio',
                'fecha_termino',
                'hora_llegada',
                'hora_salida',
                'utiliza_transporte',
                'dentro_pais',
                'medio_transporte',
                'actividad_realizada'
            ];

            $update = $informeCometido->update($request->only($form));

            $utiliza_transporte = (bool)$request->utiliza_transporte;
            if ($utiliza_transporte === true) {
                if ($request->medio_transporte) {
                    $informeCometido->transportes()->sync($request->medio_transporte);
                } else {
                    $informeCometido->transportes()->detach();
                }
            } else {
                $informeCometido->transportes()->detach();
            }

            $estados[] = [
                'status'                    => EstadoInformeCometido::STATUS_MODIFICADO,
                'informe_cometido_id'       => $informeCometido->id
            ];

            $create_status      = $informeCometido->addEstados($estados);
            $informeCometido    = $informeCometido->fresh();
            DB::commit();
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Informe de cometido {$informeCometido->codigo} modificado con éxito.",
                    'message'       => null,
                    'data'          => null
                )
            );
        } catch (\Exception $error) {
            DB::rollback();
            return response()->json($error->getMessage());
        }
    }

    public function statusInformeCometido(StatusInformeRequest $request)
    {
        try {
            $informeCometido = InformeCometido::where('uuid', $request->uuid)->firstOrFail();
            if ($informeCometido) {
                $firma_disponible = $this->isFirmaDisponibleActionPolicy($informeCometido->solicitud, 'solicitud.informes.validar');
                $status = (int)$request->status;
                switch ($status) {
                    case 1:
                        $status = EstadoInformeCometido::STATUS_APROBADO;
                        break;

                    case 2:
                        $status = EstadoInformeCometido::STATUS_RECHAZADO;
                        break;
                }
                $estados[] = [
                    'status'                    => $status,
                    'informe_cometido_id'       => $informeCometido->id,
                    'observacion'               => $request->observacion,
                    'is_subrogante'             => $firma_disponible->is_subrogante,
                    'role_id'                   => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                    'posicion_firma'            => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null
                ];

                $create_status      = $informeCometido->addEstados($estados);
                $informeCometido    = $informeCometido->fresh();
                $nom_status         = EstadoInformeCometido::STATUS_NOM[$status];

                if ($create_status) {
                    $solicitud  = $informeCometido->solicitud->fresh();
                    $navStatus  = $this->navStatusSolicitud($solicitud);

                    $last_status = $informeCometido->estados()->orderBy('id', 'DESC')->first();
                    InformeCometidoStatus::dispatch($last_status);

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Informe de cometido {$informeCometido->codigo} {$nom_status} con éxito.",
                            'message'       => null,
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'informes'      => ListInformeCometidoAdminResource::collection($solicitud->informes),
                            'nav'           => $navStatus,
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function validateUpdateSolicitud(ValidateInformeUpdateSolicitudRequest $request)
    {
        try {
            $validate_date              = $this->validateUpdateSolicitudDate($request);
            $validate_date_derecho_pago = $this->validateUpdateSolicitudDateDerechoViatico($request);
            $validate_date_year         = $this->validateDateYear($request->fecha_inicio, $request->fecha_termino);

            if (!$validate_date_year) {
                $message = "Fechas de cometido deben ser en un mismo año.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

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

            return response()->json(
                array(
                    'status'        => 'success'
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateSolicitud(UpdateSolicitudRequest $request)
    {
        try {
            DB::beginTransaction();
            $solicitud          = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $firma_disponible   = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.datos.editar-solicitud');

            if (($firma_disponible) && ($firma_disponible->id_user_ejecuted_firma === $solicitud->user_id)) {
                $this->authorize('update', $solicitud);
            } else {
                $this->authorize('updateadmin', $solicitud);
            }
            $is_update_files    = $this->validateUpdateSolicitudFiles($solicitud, $request->archivos);

            /* if (!$is_update_files) {
                $message = "Se requiere adjuntar documentos.";
                return response()->json([
                    'errors' => [
                        'file'  => [$message]
                    ]
                ], 422);
            } */
            $form = [
                'fecha_inicio',
                'fecha_termino',
                'hora_llegada',
                'hora_salida',
                'derecho_pago',
                'alimentacion_red',
                'viaja_acompaniante',
                'utiliza_transporte',
                'jornada',
                'dentro_pais',
                'tipo_comision_id',
                'actividad_realizada',
                'gastos_alimentacion',
                'gastos_alojamiento',
                'pernocta_lugar_residencia',
                'n_dias_40',
                'n_dias_100',
                'observacion_gastos',
                'observacion'
            ];

            $history_solicitud_old = $solicitud->only($form);

            $validate_date              = $this->validateUpdateSolicitudDate($request);
            $validate_days_40_100       = $this->validateSolicitudDate40100($request);
            $validate_date_derecho_pago = $this->validateUpdateSolicitudDateDerechoViatico($request);
            $validate_date_year         = $this->validateDateYear($request->fecha_inicio, $request->fecha_termino);

            if (!$validate_date_year) {
                $message = "Fechas de cometido deben ser en un mismo año.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

            if (!$validate_days_40_100) {
                $message = "Total de días al 40 y 100 no coinciden con diferencia de fecha solicitada.";
                return response()->json([
                    'errors' => [
                        'n_dias_40'  => [$message],
                        'n_dias_100' => [$message]
                    ]
                ], 422);
            }

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

            $is_update_derecho_pago = $solicitud->derecho_pago != $request->derecho_pago;

            $update             = $solicitud->update($request->only($form));
            $utiliza_transporte = (bool)$request->utiliza_transporte;

            if ($update) {
                if (!$solicitud->derecho_pago) {
                    $firmantes = $solicitud->firmantes()->whereIn('role_id', [6, 7])->where('status', true)->get();
                    if (count($firmantes) > 0) {
                        $firmantes->toQuery()->update([
                            'status' => false
                        ]);
                    }
                } else {
                    $firmantes = $solicitud->firmantes()->whereIn('role_id', [6, 7])->where('status', false)->get();
                    if (count($firmantes) > 0) {
                        $firmantes->toQuery()->update([
                            'status' => true
                        ]);
                    }
                }

                if ($solicitud->tipo_comision_id !== 5) {
                    $firmantes_capacitacion = $solicitud->firmantes()->where('role_id', 10)->where('status', true)->get();

                    if (count($firmantes_capacitacion) > 0) {
                        $firmantes_capacitacion->toQuery()->update(['status' => false]);
                    }
                }

                if ($solicitud->tipo_comision_id === 5) {
                    $firmantes_capacitacion_ok = $solicitud->firmantes()->where('role_id', 10)->where('status', false)->get();
                    $firmantes_capacitacion = $solicitud->firmantes()->where('role_id', 10)->get();

                    if (count($firmantes_capacitacion_ok)  > 0) {
                        $firmantes_capacitacion_ok->toQuery()->update(['status' => true]);
                    } else if (count($firmantes_capacitacion) === 0) {
                        $conceptoEstablecimiento = ConceptoEstablecimiento::where('establecimiento_id', $solicitud->establecimiento_id)
                            ->whereHas('concepto', function ($q) {
                                $q->where('nombre', 'CAPACITACIÓN FINANCIAMIENTO CENTRALIZADO');
                            })->first();
                        if ($conceptoEstablecimiento) {
                            $fecha_by_solicitud = Carbon::parse($solicitud->fecha_by_user)->format('Y-m-d');
                            $first_user = $conceptoEstablecimiento->funcionarios()
                                ->first();
                            if ($first_user) {
                                $nuevos_firmantes = [];
                                $firmantes_actual          = $solicitud->firmantes()->get();
                                foreach ($firmantes_actual as $firmante) {
                                    if ($firmante->role_id === 2) {
                                        $firmante_capacitacion = [
                                            'posicion_firma'    => $firmante['posicion_firma'] + 1,
                                            'solicitud_id'      => $solicitud->id,
                                            'grupo_id'          => $solicitud->grupo_id,
                                            'user_id'           => $first_user->id,
                                            'role_id'           => 10,
                                            'status'            => true,
                                            'permissions_id'    => $this->getPermissions(10, $solicitud)
                                        ];
                                        $nuevos_firmantes[] = $firmante_capacitacion;
                                    }
                                }
                                $solicitud->addFirmantes($nuevos_firmantes);
                                $solicitud          = $solicitud->fresh();
                                $firmantes          = $solicitud->firmantes()->orderBy('posicion_firma')->get();
                                $posicionesUnicas   = [];

                                foreach ($firmantes as $firmante) {
                                    if (in_array($firmante->posicion_firma, $posicionesUnicas)) {
                                        $nuevaPosicion = end($posicionesUnicas) + 1;
                                        $firmante->update(['posicion_firma' => $nuevaPosicion]);
                                        $posicionesUnicas[] = $nuevaPosicion;
                                    } else {
                                        $posicionesUnicas[] = $firmante->posicion_firma;
                                    }
                                }
                            }
                        }
                    }
                }

                if ($request->documentos) {
                    $documentos = $request->documentos;

                    $documentos_not = $solicitud->documentos()
                        ->whereNotIn('uuid', $documentos)
                        ->get();

                    if (count($documentos_not) > 0) {
                        foreach ($documentos_not as $documento_not) {
                            $documento_not->delete();
                        }
                    }
                } else {
                    $documentos = $solicitud->documentos()
                        ->get();

                    if (count($documentos) > 0) {
                        foreach ($documentos as $documento) {
                            $documento->delete();
                        }
                    }
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
                            'nombre'        => "{$file->getClientOriginalName()}",
                            'size'          => $file->getSize(),
                            'format'        => $file->getMimeType(),
                            'extension'     => $file->getClientOriginalExtension(),
                            'is_valid'      => $file->isValid(),
                            'solicitud_id'  => $solicitud->id,
                            'user_id'       => $solicitud->user_id,
                            'model'         => Documento::MODEL_SOLICITUD
                        ]);
                    }
                }


                if ($request->motivos_cometido) {
                    $solicitud->motivos()->sync($request->motivos_cometido);
                }

                $dentro_pais = (bool)$request->dentro_pais;
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

                if ($utiliza_transporte === true) {
                    if ($request->medio_transporte) {
                        $solicitud->transportes()->sync($request->medio_transporte);
                    }
                } else {
                    $solicitud->transportes()->detach();
                }

                $solicitud  = $solicitud->fresh();
                $estados    = [];
                $abreNombres        = Auth::user()->abreNombres();
                if (($firma_disponible) && ($firma_disponible->id_user_ejecuted_firma === $solicitud->user_id)) {
                    $estados[] = [
                        'status'                    => EstadoSolicitud::STATUS_MODIFICADA,
                        'is_reasignado'             => false,
                        'posicion_firma_s'          => $firma_disponible ? $firma_disponible->posicion_firma : null,
                        'history_solicitud_old'     => json_encode($history_solicitud_old),
                        'history_solicitud_new'     => json_encode($solicitud->only($form)),
                        'solicitud_id'              => $solicitud->id,
                        'posicion_firma'            => $firma_disponible ? $firma_disponible->posicion_firma : null,
                        's_firmante_id'             => $firma_disponible ? $firma_disponible->id_firma : null,
                        'user_id'                   => $firma_disponible ? $firma_disponible->id_user_ejecuted_firma : null,
                        'is_subrogante'             => $firma_disponible ? $firma_disponible->is_subrogante : false
                    ];
                    $next_url = '/mi-cuenta/solicitudes';
                } else {
                    if (($firma_disponible) && ($firma_disponible->posicion_firma === $solicitud->posicion_firma_actual)) {
                        $estados[] = [
                            'status'                    => EstadoSolicitud::STATUS_MODIFICADA,
                            'is_reasignado'             => false,
                            'posicion_firma_s'          => $firma_disponible ? $firma_disponible->posicion_firma : null,
                            'history_solicitud_old'     => json_encode($history_solicitud_old),
                            'history_solicitud_new'     => json_encode($solicitud->only($form)),
                            'solicitud_id'              => $solicitud->id,
                            'posicion_firma'            => $firma_disponible ? $firma_disponible->posicion_firma : null,
                            's_firmante_id'             => $firma_disponible ? $firma_disponible->id_firma : null,
                            'user_id'                   => $firma_disponible ? $firma_disponible->id_user_ejecuted_firma : null,
                            'is_subrogante'             => $firma_disponible ? $firma_disponible->is_subrogante : false,
                            'observacion'               => "Modificada por usuario {$abreNombres} desde su firma {$firma_disponible->id_firma}."
                        ];

                        $nom_status = EstadoSolicitud::STATUS_NOM[EstadoSolicitud::STATUS_APROBADO];
                        $estados[]          = [
                            'status'                    => EstadoSolicitud::STATUS_APROBADO,
                            'is_reasignado'             => false,
                            'posicion_firma_s'          => $firma_disponible ? $firma_disponible->posicion_firma : null,
                            'history_solicitud_old'     => json_encode($history_solicitud_old),
                            'history_solicitud_new'     => json_encode($solicitud->only($form)),
                            'solicitud_id'              => $solicitud->id,
                            'posicion_firma'            => $firma_disponible ? $firma_disponible->posicion_firma : null,
                            's_firmante_id'             => $firma_disponible ? $firma_disponible->id_firma : null,
                            'user_id'                   => $firma_disponible ? $firma_disponible->id_user_ejecuted_firma : null,
                            'is_subrogante'             => $firma_disponible ? $firma_disponible->is_subrogante : false,
                            'observacion'               => "GECOM: $nom_status por usuario {$abreNombres} desde su firma {$firma_disponible->id_firma}."
                        ];
                    } else {
                        $firma_funcionario  = $solicitud->firmantes()->where('role_id', 1)->first();
                        $estados[]          = [
                            'status'                    => EstadoSolicitud::STATUS_MODIFICADA,
                            'is_reasignado'             => false,
                            'posicion_firma_s'          => 0,
                            'history_solicitud_old'     => json_encode($history_solicitud_old),
                            'history_solicitud_new'     => json_encode($solicitud->only($form)),
                            'solicitud_id'              => $solicitud->id,
                            'posicion_firma'            => 0,
                            's_firmante_id'             => $firma_funcionario ? $firma_funcionario->id : null,
                            'user_id'                   => $solicitud->user_id,
                            'is_subrogante'             => $firma_disponible ? $firma_disponible->is_subrogante : false,
                            'observacion'               => "Modificada por usuario {$abreNombres} desde su firma {$firma_disponible->id_firma}."
                        ];
                    }
                    $next_url = '/firmante/solicitudes';
                }

                $create_status  = $solicitud->addEstados($estados);

                if ($is_update_derecho_pago) {
                    $firmantes      = $solicitud->firmantes()->whereIn('role_id', [2, 3])->get();
                    $emails_copy    = $firmantes->pluck('funcionario.email')->toArray();
                    SolicitudUpdated::dispatch($solicitud, $emails_copy);
                }

                DB::commit();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud con código {$solicitud->codigo} modificada con éxito.",
                        'message'       => null,
                        'data'          => null,
                        'next_url'      => $next_url
                    )
                );
            }
        } catch (\Exception $error) {
            DB::rollback();
            Log::info($error->getMessage());
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
    public function getSolicitudUpdate($uuid)
    {
        try {
            $solicitud = Solicitud::where('uuid', $uuid)->firstOrFail();

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

            if (!$validate_date_year) {
                $message = "Fechas de cometido deben ser en un mismo año.";
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => [$message],
                        'fecha_termino' => [$message]
                    ]
                ], 422);
            }

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

            return response()->json(
                array(
                    'status'        => 'success'
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateSolicitudDate40100($request)
    {
        try {
            $fecha_inicio    = Carbon::parse($request->fecha_inicio);
            $fecha_termino   = Carbon::parse($request->fecha_termino);
            $diff_days       = $fecha_inicio->diffInDays($fecha_termino) + 1;
            $diff_days       = $diff_days * 2;
            $n_dias_40       = $request->n_dias_40 != null ? (int)$request->n_dias_40 : 0;
            $n_dias_100      = $request->n_dias_100 != null ? (int)$request->n_dias_100 : 0;
            $total_40_100    = $n_dias_40 + $n_dias_100;
            if ($total_40_100 > $diff_days) {
                return false;
            }
            return true;
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

    private function validateSolicitudDate($request)
    {
        try {
            $existe         = false;
            $fecha_inicio   = $request->fecha_inicio;
            $fecha_termino  = $request->fecha_termino;

            $hora_llegada  = $request->hora_llegada;
            $hora_salida   = $request->hora_salida;
            $solicitudes = Solicitud::where('user_id', $request->user_id)
                ->whereIn('status', [0, 1])
                ->where('derecho_pago', false)
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
                    ->whereIn('status', [0, 1])
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
            $solicitudes = Solicitud::where('uuid', '!=', $request->solicitud_uuid)
                ->where('user_id', $request->user_id)
                ->whereIn('status', [0, 1])
                ->where('derecho_pago', false)
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

    private function validateUpdateSolicitudDateDerechoViatico($request)
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
                $solicitudes = Solicitud::where('uuid', '!=', $request->solicitud_uuid)
                    ->where('user_id', $request->user_id)
                    ->whereIn('status', [0, 1])
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

    private function validateUpdateStatusSolicitud($solicitud)
    {
        try {
            $last_status = $solicitud->estados()->orderBy('id', 'DESC')->first();

            if (($last_status) && ($last_status->status === EstadoSolicitud::STATUS_INGRESADA || $last_status->status === EstadoSolicitud::STATUS_PENDIENTE)) {
                return true;
            }
            return false;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateUpdateSolicitudFiles($solicitud, $archivos)
    {
        try {
            $lastStatus = $solicitud->estados()
                ->orderBy('id', 'DESC')
                ->first();

            $countDocumentos = $solicitud->documentos()->count();
            if ($lastStatus->status === EstadoSolicitud::STATUS_RECHAZADO && $lastStatus->motivo_rechazo === EstadoSolicitud::RECHAZO_3 && ($countDocumentos <= 0 || !$archivos)) {
                return false;
            }
            return true;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return false;
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
