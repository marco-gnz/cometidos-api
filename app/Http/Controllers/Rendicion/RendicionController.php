<?php

namespace App\Http\Controllers\Rendicion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rendicion\StoreRendicionRequest;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoResource;
use App\Http\Resources\Rendicion\RendicionGastoResource;
use App\Http\Resources\Rendicion\SolicitudesRendicionRequest;
use App\Models\Documento;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RendicionController extends Controller
{
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

    public function solicitudesRendicionGastos()
    {
        try {
            $auth           = Auth::user();
            if ($auth) {
                $solicitudes = Solicitud::where('user_id', $auth->id)->whereIn('last_status', [0, 1, 2])->get();

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

    public function getProcesoRendiciones()
    {
        try {
            $auth           = Auth::user();
            if ($auth) {
                $solicitudes = ProcesoRendicionGasto::whereHas('solicitud', function ($q) use ($auth) {
                    $q->where('user_id', $auth->id);
                })->get();

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => null,
                        'message'       => null,
                        'data'          => ProcesoRendicionGastoResource::collection($solicitudes)
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

    public function storeRendicion(Request $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
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


            $is_avion = $solicitud->transportes()->where('solicitud_transporte.transporte_id', 1)->exists();
            $validator = Validator::make($data, [
                'solicitud_uuid'                            => ['required'],
                'archivos'                                  => ['nullable'],
                'actividades'                               => ['present', 'required', 'array'],
                'actividades.*.id'                          => ['required'],
                'actividades.*.rinde_gasto'                 => ['required'],
                'actividades.*.mount' => [
                    function ($attribute, $value, $fail) {
                        $index = preg_replace('/[^0-9]/', '', $attribute);
                        $rinde_gasto = "actividades.{$index}.rinde_gasto";
                        if(request()->input($attribute) === null && request()->input($rinde_gasto) != 0){
                            $fail("El monto es obligatorio");
                        }
                    },
                ],
                'actividades.*.rinde_gastos_servicio' => [
                    function ($attribute, $value, $fail) use($is_avion){
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
                $proceso_rendicion_gasto = ProcesoRendicionGasto::create(['solicitud_id' => $solicitud->id]);
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
                                'proceso_rendicion_gasto_id'    => $proceso_rendicion_gasto->id
                            ]);
                        }
                    }

                    $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

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
}
