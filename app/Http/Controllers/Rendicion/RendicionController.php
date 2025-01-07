<?php

namespace App\Http\Controllers\Rendicion;

use App\Events\ProcesoRendicionGastoCreated;
use App\Events\ProcesoRendicionGastoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcesoRendicion\StoreProcesoRendicionRequest;
use App\Http\Requests\Rendicion\AnularRendicionRequest;
use App\Http\Requests\Rendicion\AprobarRendicionRequest;
use App\Http\Requests\Rendicion\StoreRendicionRequest;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoUpdateResource;
use App\Http\Resources\Rendicion\RendicionGastoResource;
use App\Http\Resources\Rendicion\SolicitudesRendicionRequest;
use App\Models\Documento;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Traits\FirmaDisponibleTrait;
use App\Traits\StatusSolicitudTrait;

class RendicionController extends Controller
{
    use FirmaDisponibleTrait, StatusSolicitudTrait;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    protected $customMessages = [
        'actividades.required'                      => 'La :attribute es obligatoria',
        'actividades.*.rinde_gasto.required'        => 'El :attribute es obligatorio',
        'actividades.*.mount.required'              => 'El :attribute es obligatorio',
    ];

    protected $customAttributes = [
        'actividades'           => 'actividad',
        'actividades.*.mount'   => 'monto'
    ];

    public function solicitudesRendicionGastos(Request $request)
    {
        try {
            $auth           = Auth::user();
            if ($auth) {
                $query = Solicitud::where('user_id', $auth->id)
                    ->where('status', '!=', Solicitud::STATUS_ANULADO)
                    ->whereYear('fecha_inicio', $request->year);

                if ($request->month) {
                    $query->whereMonth('fecha_inicio', $request->month);
                }

                $solicitudes = $query->orderBy('fecha_inicio', 'DESC')->get();

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => null,
                        'message'       => null,
                        'data'          => SolicitudesRendicionRequest::collection($solicitudes)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getProcesoRendiciones(Request $request)
    {
        try {
            $auth           = Auth::user();
            if ($auth) {
                $proceso_rendiciones = ProcesoRendicionGasto::whereHas('solicitud', function ($q) use ($auth) {
                    $q->where('user_id', $auth->id);
                })
                    ->searchInput($request->input)
                    ->periodoIngresoProceso($request->periodo_ingreso_rendicion)
                    ->estadoRendicion($request->estados_rendicion_id)
                    ->orderBy('id', 'DESC')->paginate(20);

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => null,
                        'message'       => null,
                        'pagination' => [
                            'total'         => $proceso_rendiciones->total(),
                            'total_desc'    => $proceso_rendiciones->total() > 1 ? "{$proceso_rendiciones->total()} resultados" : "{$proceso_rendiciones->total()} resultado",
                            'current_page'  => $proceso_rendiciones->currentPage(),
                            'per_page'      => $proceso_rendiciones->perPage(),
                            'last_page'     => $proceso_rendiciones->lastPage(),
                            'from'          => $proceso_rendiciones->firstItem(),
                            'to'            => $proceso_rendiciones->lastPage()
                        ],
                        'data'          => ProcesoRendicionGastoResource::collection($proceso_rendiciones)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getProcesoRendicion($uuid)
    {
        try {
            $rendicion = ProcesoRendicionGasto::where('uuid', $uuid)->firstOrFail();
            $navStatus  = $this->navStatusRendicion($rendicion);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ProcesoRendicionGastoDetalleResource::make($rendicion),
                    'nav'           => $navStatus
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getProcesoRendicionUpdate($uuid)
    {
        try {
            $rendicion = ProcesoRendicionGasto::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $rendicion);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ProcesoRendicionGastoUpdateResource::make($rendicion)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function transformData($data)
    {
        array_walk_recursive($data, function (&$value) {

            if (is_string($value)) {
                $decodedValue = json_decode($value, true);
                if ($decodedValue !== null) {
                    $value = $decodedValue;
                }
            }
        });

        if (!isset($data['observacion'])) {
            $data['observacion'] = null;
        }

        if (isset($data['archivos']) && !empty($data['archivos'])) {
            array_walk_recursive($data, function (&$value) {
                if (is_string($value)) {
                    $decodedValue = json_decode($value, true);
                    if ($decodedValue !== null) {
                        $value = $decodedValue;
                    }
                }
            });

            if (isset($data['actividades']) && !empty($data['actividades'])) {
                foreach ($data['actividades'] as $index => $value) {
                    if ($value === null) {
                        $data['actividades'][$index] = [];
                    }
                }
            }
        }
        return $data;
    }

    public function storeRendicion(Request $request)
    {
        try {

            $transformedData = $this->transformData($request->all());

            $storeProcesoRendicionRequest = new StoreProcesoRendicionRequest();
            $storeProcesoRendicionRequest->merge($transformedData);
            $storeProcesoRendicionRequest->solicitud_uuid = $transformedData['solicitud_uuid'];

            $validator = Validator::make(
                $transformedData,
                $storeProcesoRendicionRequest->rules(),
                $storeProcesoRendicionRequest->messages(),
                $storeProcesoRendicionRequest->attributes()
            );

            $storeProcesoRendicionRequest->withValidator($validator);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validatedData = $validator->validated();

            $solicitud = Solicitud::where('uuid', $validatedData['solicitud_uuid'])->firstOrFail();

            if ($solicitud) {
                $actividades = [];
                $observacion = null;
                if (isset($validatedData['observacion'])) {
                    $observacion = $validatedData['observacion'];
                }
                $proceso_rendicion_gasto = ProcesoRendicionGasto::create([
                    'solicitud_id'   => $solicitud->id,
                    'observacion'    => $observacion
                ]);
                if ($proceso_rendicion_gasto) {
                    if ($validatedData['actividades']) {
                        foreach ($validatedData['actividades'] as $actividad) {
                            $actividad_id               = (int)$actividad['id'];
                            $rinde_gasto                = (bool)$actividad['rinde_gasto'];
                            $mount                      = (int)$actividad['mount'];
                            $rinde_gastos_servicio      = (bool)$actividad['rinde_gastos_servicio'];

                            $actividades[] = [
                                'rinde_gasto'                   => $rinde_gasto,
                                'mount'                         => $rinde_gasto ? $mount : null,
                                'rinde_gastos_servicio'         => $rinde_gastos_servicio,
                                'proceso_rendicion_gasto_id'    => $proceso_rendicion_gasto->id,
                                'actividad_gasto_id'            => $actividad_id
                            ];
                        }
                    }
                    $proceso_rendicion_gasto->addRendiciones($actividades);

                    if ($validatedData['archivos']) {
                        foreach ($validatedData['archivos'] as $file) {
                            $fecha_solicitud    = Carbon::parse($solicitud->fecha_inicio);
                            $year               = $fecha_solicitud->format('Y');
                            $month              = $fecha_solicitud->format('m');
                            $fileName           = 'rendicion_de_gastos/' . $solicitud->funcionario->rut . '/' . $year . '/' . $month . '/' . $file->getClientOriginalName();
                            $path               = Storage::disk('public')->putFileAs('archivos', $file, $fileName);

                            $store = Documento::create([
                                'url'           => $path,
                                'nombre'        => $file->getClientOriginalName(),
                                'size'          => $file->getSize(),
                                'format'        => $file->getMimeType(),
                                'extension'     => $file->getClientOriginalExtension(),
                                'is_valid'      => $file->isValid(),
                                'solicitud_id'  => $solicitud->id,
                                'proceso_rendicion_gasto_id'    => $proceso_rendicion_gasto->id,
                                'user_id'       => $solicitud->user_id,
                                'model'         => Documento::MODEL_RENDICION
                            ]);
                        }
                    }

                    $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

                    if ($proceso_rendicion_gasto) {
                        $firma_disponible = $this->isFirmaDisponibleActionPolicy($proceso_rendicion_gasto, null);
                        $estado = [
                            'status'                => EstadoProcesoRendicionGasto::STATUS_INGRESADA,
                            'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                            'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                            'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null,
                            'is_subrogante'         => $firma_disponible->is_subrogante
                        ];
                        $status = EstadoProcesoRendicionGasto::create($estado);
                        ProcesoRendicionGastoCreated::dispatch($proceso_rendicion_gasto);
                    }

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Rendición de gastos ingresada con éxito",
                            'message'       => null,
                            'data'          => ProcesoRendicionGastoResource::make($proceso_rendicion_gasto)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    /* public function storeRendicion(StoreProcesoRendicionRequest $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            // Obtener todos los datos de la solicitud
            $data = $request->all();
            return $data;
            // Decodificar el campo "medio_transporte" si es una cadena JSON
            array_walk_recursive($data, function (&$value) {

                if (is_string($value)) {
                    $decodedValue = json_decode($value, true);
                    if ($decodedValue !== null) {
                        $value = $decodedValue;
                    }
                }
            });

            if ((isset($data['actividades'])) && ($data['actividades'])) {
                foreach ($data['actividades'] as $value) {
                    if ($value === null) {
                        $data['actividades'] = [];
                    }
                }
            }
            Log::info($data['archivos']);
            $is_avion = $solicitud->transportes()->where('solicitud_transporte.transporte_id', 1)->exists();
            $validator = Validator::make($data, [
                'solicitud_uuid'                            => ['required'],
                'observacion' => [
                    function ($attribute, $value, $fail) use ($data) {
                        $actividades        = $data['actividades'];
                        $actividadOtros     = collect($actividades)->firstWhere('id', 12);

                        if ($actividadOtros && $actividadOtros['rinde_gasto'] !== 0 && empty($value)) {
                            $fail("La observación es requerida si selecciona la opción Otros. Por favor, detallar actividad.");
                        }
                    },
                ],
                'archivos'                                  => ['nullable'],
                'actividades'                               => ['present', 'required', 'array'],
                'actividades.*.id'                          => ['required'],
                'actividades.*.rinde_gasto'                 => ['required'],
                'actividades.*.mount' => [
                    function ($attribute, $value, $fail) use ($data) {
                        $index = preg_replace('/[^0-9]/', '', $attribute);
                        $rinde_gasto = "actividades.{$index}.rinde_gasto";

                        if ($value <= 0) {
                            $actividades = $data['actividades'];
                            $activitiesWithPositiveMountAndRindeGasto = collect($actividades)->filter(function ($actividad) {
                                return $actividad['mount'] > 0 && $actividad['rinde_gasto'] === 1;
                            });
                            if ($activitiesWithPositiveMountAndRindeGasto->isEmpty()) {
                                $fail("Al menos una actividad debe tener un monto mayor a $0");
                            }
                        }

                        if (request()->input($attribute) === null && request()->input($rinde_gasto) != 0) {
                            $fail("El monto es obligatorio");
                        }
                    },
                ],
                'actividades.*.rinde_gastos_servicio' => [
                    function ($attribute, $value, $fail) use ($is_avion) {
                        $index              = preg_replace('/[^0-9]/', '', $attribute);
                        $rinde_gasto        = "actividades.{$index}.rinde_gasto";
                        $id_actividad       = "actividades.{$index}.id";
                        $rinde_gasto_value  = request()->input($rinde_gasto);
                        $actividad_id_value = request()->input($id_actividad);
                        $rinde_gastos_servicio_value = request()->input($attribute);

                        if ($actividad_id_value === 1 && $rinde_gastos_servicio_value === null && $is_avion) {
                            $fail("Respuesta es obligatoria");
                        }
                    },
                ],
            ],  $this->customMessages, $this->customAttributes);

            if ($validator->fails()) {
                // Manejar los errores de validación aquí
                return response()->json($validator->errors(), 400);
            }
            if ($solicitud) {
                $actividades = [];
                $observacion = null;
                if (isset($data['observacion'])) {
                    $observacion = $data['observacion'];
                }
                $proceso_rendicion_gasto = ProcesoRendicionGasto::create([
                    'solicitud_id'   => $solicitud->id,
                    'observacion'    => $observacion
                ]);
                if ($proceso_rendicion_gasto) {
                    if ($data['actividades']) {
                        foreach ($data['actividades'] as $actividad) {
                            $actividad_id               = (int)$actividad['id'];
                            $rinde_gasto                = (bool)$actividad['rinde_gasto'];
                            $mount                      = (int)$actividad['mount'];
                            $rinde_gastos_servicio      = (bool)$actividad['rinde_gastos_servicio'];

                            $actividades[] = [
                                'rinde_gasto'                   => $rinde_gasto,
                                'mount'                         => $rinde_gasto ? $mount : null,
                                'rinde_gastos_servicio'         => $rinde_gastos_servicio,
                                'proceso_rendicion_gasto_id'    => $proceso_rendicion_gasto->id,
                                'actividad_gasto_id'            => $actividad_id
                            ];
                        }
                    }
                    $proceso_rendicion_gasto->addRendiciones($actividades);

                    if (isset($data['archivos'])) {
                        $files = $data['archivos'];
                        foreach ($files as $file) {
                            $fecha_solicitud    = Carbon::parse($solicitud->fecha_inicio);
                            $year               = $fecha_solicitud->format('Y');
                            $month              = $fecha_solicitud->format('m');
                            $fileName           = 'rendicion_de_gastos/' . $solicitud->funcionario->rut . '/' . $year . '/' . $month . '/' . $file->getClientOriginalName();
                            $path               = Storage::disk('public')->putFileAs('archivos', $file, $fileName);

                            $store = Documento::create([
                                'url'                           => $path,
                                'nombre'                        => $file->getClientOriginalName(),
                                'size'                          => $file->getSize(),
                                'format'                        => $file->getMimeType(),
                                'extension'                     => $file->getClientOriginalExtension(),
                                'is_valid'                      => $file->isValid(),
                                'solicitud_id'                  => $solicitud->id,
                                'user_id'                       => $solicitud->user_id,
                                'proceso_rendicion_gasto_id'    => $proceso_rendicion_gasto->id,
                                'model'                         => Documento::MODEL_RENDICION
                            ]);
                        }
                    }

                    $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

                    if ($proceso_rendicion_gasto) {
                        $firma_disponible = $this->isFirmaDisponibleActionPolicy($proceso_rendicion_gasto, null);
                        $estado = [
                            'status'                => EstadoProcesoRendicionGasto::STATUS_INGRESADA,
                            'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                            'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                            'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null,
                            'is_subrogante'         => $firma_disponible->is_subrogante
                        ];
                        $status = EstadoProcesoRendicionGasto::create($estado);
                        ProcesoRendicionGastoCreated::dispatch($proceso_rendicion_gasto);
                    }

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Rendición de gastos ingresada con éxito",
                            'message'       => null,
                            'data'          => ProcesoRendicionGastoResource::make($proceso_rendicion_gasto)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    } */

    public function updateRendicion(Request $request)
    {
        try {
            $proceso_rendicion_gasto = ProcesoRendicionGasto::where('uuid', $request->uuid)->firstOrFail();
            $this->authorize('update', $proceso_rendicion_gasto);
            // Obtener todos los datos de la solicitud
            $data = $request->all();

            // Decodificar el campo "medio_transporte" si es una cadena JSON
            array_walk_recursive($data, function (&$value) {

                if (is_string($value)) {
                    $decodedValue = json_decode($value, true);
                    if ($decodedValue !== null) {
                        $value = $decodedValue;
                    }
                }
            });

            if ((isset($data['actividades'])) && ($data['actividades'])) {
                foreach ($data['actividades'] as $value) {
                    if ($value === null) {
                        $data['actividades'] = [];
                    }
                }
            }

            if (!isset($data['observacion'])) {
                $data['observacion'] = null;
            }

            $is_avion = $proceso_rendicion_gasto->solicitud->transportes()->where('solicitud_transporte.transporte_id', 1)->exists();
            $validator = Validator::make($data, [
                'observacion' => [
                    'required_if:actividades.*.id,12',
                    function ($attribute, $value, $fail) use ($data) {
                        $actividades = $data['actividades'];
                        $actividadOtros = collect($actividades)->firstWhere('id', 12);

                        if ($actividadOtros && $actividadOtros['rinde_gasto'] !== 0 && empty($value)) {
                            $fail("La observación es requerida si selecciona la opción Otros. Por favor, detallar actividad.");
                        }
                    },
                ],
                'documentos'                                => ['nullable'],
                'archivos'                                  => [
                    function ($attribute, $value, $fail) {
                        if (empty(request()->input('documentos')) && empty($value)) {
                            $fail('Debe adjuntar archivos.');
                        }
                    }
                ],
                'actividades'                               => ['present', 'required', 'array'],
                'actividades.*.id'                          => ['required'],
                'actividades.*.rinde_gasto'                 => ['required'],
                'actividades.*.mount' => [
                    function ($attribute, $value, $fail) use ($data) {
                        $index = preg_replace('/[^0-9]/', '', $attribute);
                        $rinde_gasto = "actividades.{$index}.rinde_gasto";

                        if (request()->input($attribute) === null && request()->input($rinde_gasto) != 0) {
                            $fail("El monto es obligatorio");
                        }

                        if ($value <= 0) {
                            $actividades = $data['actividades'];
                            $activitiesWithPositiveMountAndRindeGasto = collect($actividades)->filter(function ($actividad) {
                                return $actividad['mount'] > 0 && $actividad['rinde_gasto'] === 1;
                            });

                            if ($activitiesWithPositiveMountAndRindeGasto->isEmpty()) {
                                $fail("Al menos una actividad debe tener un monto mayor a $0");
                            }
                        }
                    },
                ],
                'actividades.*.rinde_gastos_servicio' => [
                    function ($attribute, $value, $fail) use ($is_avion) {
                        $index              = preg_replace('/[^0-9]/', '', $attribute);
                        $rinde_gasto        = "actividades.{$index}.rinde_gasto";
                        $id_actividad       = "actividades.{$index}.id";
                        $rinde_gasto_value  = request()->input($rinde_gasto);
                        $actividad_id_value = request()->input($id_actividad);
                        $rinde_gastos_servicio_value = request()->input($attribute);

                        if ($actividad_id_value === 1 && $rinde_gastos_servicio_value === null && $is_avion) {
                            $fail("Respuesta es obligatoria");
                        }
                    },
                ],
            ],  $this->customMessages, $this->customAttributes);

            if ($validator->fails()) {
                // Manejar los errores de validación aquí
                return response()->json($validator->errors(), 400);
            }
            if ($proceso_rendicion_gasto) {
                $actividades = [];
                if (isset($data['observacion'])) {
                    $proceso_rendicion_gasto->update([
                        'observacion'    => $data['observacion']
                    ]);
                }

                if ($data['actividades']) {
                    $delete = $proceso_rendicion_gasto->rendiciones()->delete();

                    if ($delete) {
                        foreach ($data['actividades'] as $actividad) {
                            $actividad_id               = (int)$actividad['id'];
                            $rinde_gasto                = (bool)$actividad['rinde_gasto'];
                            $mount                      = (int)$actividad['mount'];
                            $rinde_gastos_servicio      = (bool)$actividad['rinde_gastos_servicio'];

                            $actividades[] = [
                                'rinde_gasto'                   => $rinde_gasto,
                                'mount'                         => $rinde_gasto ? $mount : null,
                                'rinde_gastos_servicio'         => $rinde_gastos_servicio,
                                'proceso_rendicion_gasto_id'    => $proceso_rendicion_gasto->id,
                                'actividad_gasto_id'            => $actividad_id
                            ];
                        }
                        $proceso_rendicion_gasto->addRendiciones($actividades);
                    }
                }

                if (isset($data['documentos'])) {
                    $documentos = $data['documentos'];

                    $documentos_not = $proceso_rendicion_gasto->documentos()
                        ->whereNotIn('uuid', $documentos)
                        ->get();

                    if (count($documentos_not) > 0) {
                        foreach ($documentos_not as $documento_not) {
                            $documento_not->delete();
                        }
                    }
                } else {
                    $documentos = $proceso_rendicion_gasto->documentos()
                        ->get();

                    if (count($documentos) > 0) {
                        foreach ($documentos as $documento) {
                            $documento->delete();
                        }
                    }
                }

                if (isset($data['archivos'])) {
                    $files = $data['archivos'];
                    foreach ($files as $file) {
                        $fecha_solicitud    = Carbon::parse($proceso_rendicion_gasto->solicitud->fecha_inicio);
                        $year               = $fecha_solicitud->format('Y');
                        $month              = $fecha_solicitud->format('m');
                        $fileName           = 'rendicion_de_gastos/' . $proceso_rendicion_gasto->solicitud->funcionario->rut . '/' . $year . '/' . $month . '/' . $file->getClientOriginalName();
                        $path               = Storage::disk('public')->putFileAs('archivos', $file, $fileName);

                        $store = Documento::create([
                            'url'                           => $path,
                            'nombre'                        => $file->getClientOriginalName(),
                            'size'                          => $file->getSize(),
                            'format'                        => $file->getMimeType(),
                            'extension'                     => $file->getClientOriginalExtension(),
                            'is_valid'                      => $file->isValid(),
                            'solicitud_id'                  => $proceso_rendicion_gasto->solicitud->id,
                            'user_id'                       => $proceso_rendicion_gasto->solicitud->user_id,
                            'proceso_rendicion_gasto_id'    => $proceso_rendicion_gasto->id,
                            'model'                         => Documento::MODEL_RENDICION
                        ]);
                    }
                }

                $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

                if ($proceso_rendicion_gasto) {
                    $firma_disponible = $this->isFirmaDisponibleActionPolicy($proceso_rendicion_gasto, null);
                    $estado = [
                        'status'                => EstadoProcesoRendicionGasto::STATUS_MODIFICADA,
                        'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                        'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                        'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null,
                        'is_subrogante'         => $firma_disponible->is_subrogante
                    ];
                    $status = EstadoProcesoRendicionGasto::create($estado);
                }
                $next_firma_roceso_rendicion = $this->nextFirmaProcesoRendicion(true, $proceso_rendicion_gasto->solicitud, $firma_disponible);

                if ($next_firma_roceso_rendicion) {
                    $proceso_rendicion_gasto->update([
                        'posicion_firma_ok' =>  $next_firma_roceso_rendicion->posicion_firma
                    ]);
                }

                $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Rendición de gastos $proceso_rendicion_gasto->n_folio modificada con éxito",
                        'message'       => null,
                        'data'          => ProcesoRendicionGastoResource::make($proceso_rendicion_gasto)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function deleteRendicion($uuid)
    {
        try {
            $proceso_rendicion_gasto = ProcesoRendicionGasto::where('uuid', $uuid)->firstOrFail();
            $this->authorize('delete', $proceso_rendicion_gasto);
            if ($proceso_rendicion_gasto) {
                $delete = $proceso_rendicion_gasto->delete();

                if ($delete) {
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => 'Rendición eliminada con éxito.',
                            'message'       => null
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function anularRendicion(AnularRendicionRequest $request)
    {
        try {
            $proceso_rendicion_gasto = ProcesoRendicionGasto::where('uuid', $request->uuid)->firstOrFail();
            $this->authorize('anular', $proceso_rendicion_gasto);
            $firma_disponible = $this->isFirmaDisponibleActionPolicy($proceso_rendicion_gasto->solicitud, 'rendicion.firma.anular');
            $estado = [
                'observacion'           => $request->observacion,
                'status'                => EstadoProcesoRendicionGasto::STATUS_ANULADO,
                'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null,
                'is_subrogante'         => $firma_disponible->is_subrogante
            ];
            $status = EstadoProcesoRendicionGasto::create($estado);

            $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

            $last_status = $proceso_rendicion_gasto->estados()->orderBy('id', 'DESC')->first();
            ProcesoRendicionGastoStatus::dispatch($last_status);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => 'Rendición anulada con éxito.',
                    'message'       => null,
                    'data'          => ProcesoRendicionGastoResource::make($proceso_rendicion_gasto)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function aprobarRendicion(AprobarRendicionRequest $request)
    {
        try {
            $proceso_rendicion_gasto    = ProcesoRendicionGasto::where('uuid', $request->uuid)->firstOrFail();
            $this->authorize('aprobar', $proceso_rendicion_gasto);

            if ($proceso_rendicion_gasto->status === EstadoProcesoRendicionGasto::STATUS_VERIFICADO) {
                $status = EstadoProcesoRendicionGasto::STATUS_APROBADO_N;
                if ($proceso_rendicion_gasto->isRendicionesModificadas()) {
                    $status = EstadoProcesoRendicionGasto::STATUS_APROBADO_S;
                }
                $last_cuenta_bancaria = $proceso_rendicion_gasto->solicitud->funcionario->lastCuentaBancaria();
                if (!$last_cuenta_bancaria) {
                    return response()->json([
                        'errors' =>  $proceso_rendicion_gasto->solicitud->funcionario->abreNombres() . " no registra cuenta bancaria habilitada o algún medio de pago."
                    ], 422);
                }

                $proceso_rendicion_gasto->update([
                    'cuenta_bancaria_id'    => $last_cuenta_bancaria->id
                ]);
            } else if ($proceso_rendicion_gasto->status === EstadoProcesoRendicionGasto::STATUS_INGRESADA || $proceso_rendicion_gasto->status === EstadoProcesoRendicionGasto::STATUS_MODIFICADA) {
                $status = EstadoProcesoRendicionGasto::STATUS_APROBADO_JD;
            }

            $firma_disponible = $this->isFirmaDisponibleActionPolicy($proceso_rendicion_gasto->solicitud, 'rendicion.firma.validar');
            $estado = [
                'status'                => $status,
                'observacion'           => $request->observacion,
                'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null,
                'is_subrogante'         => $firma_disponible->is_subrogante
            ];
            $status_r = EstadoProcesoRendicionGasto::create($estado);

            $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

            $last_status = $proceso_rendicion_gasto->estados()->orderBy('id', 'DESC')->first();
            ProcesoRendicionGastoStatus::dispatch($last_status);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => 'Rendición aprobada con éxito.',
                    'message'       => null,
                    'data'          => ProcesoRendicionGastoResource::make($proceso_rendicion_gasto)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
