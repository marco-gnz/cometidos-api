<?php

namespace App\Http\Controllers\Solicitud;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\StoreSolicitudRequest;
use App\Http\Requests\Solicitud\ValidateFileSolicitudRequest;
use App\Http\Requests\Solicitud\ValidateInformeSolicitudRequest;
use App\Models\Documento;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SolicitudController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    protected $customMessages = [
        'fecha_inicio.required'                 => 'La :attribute es obligatoria',
        'fecha_inicio.date'                     => 'La :attribute debe ser una fecha válida',
        'fecha_inicio.before_or_equal'          => 'La :attribute debe ser anterior a fecha de término',

        'fecha_termino.required'                => 'La :attribute es obligatoria',
        'fecha_termino.date'                    => 'La :attribute debe ser una fecha válida',
        'fecha_termino.after_or_equal'          => 'La :attribute debe ser superior a fecha de inicio',

        'hora_salida.required'                  => 'La :attribute es obligatoria',
        'hora_salida.after_or_equal'            => 'La :attribute debe ser superior a :hora_llegada',

        'hora_llegada.required'                 => 'La :attribute es obligatoria',
        'hora_salida.before_or_equal'           => 'La :attribute debe ser superior a :hora_salida',

        'derecho_pago.required'                 => 'El :attribute es obligatorio',

        'motivos_cometido.required'             => 'El :attribute es obligatorio',

        'lugares_cometido.required'             => 'El :attribute es obligatorio',

        'actividad_realizada.required'          => 'La :attribute es obligatoria',


        'medio_transporte.required'             => 'El :attribute es obligatorio',

        'gastos_alimentacion.required'          => 'El :attribute es obligatorio',

        'gastos_alojamiento.required'           => 'El :attribute es obligatorio',

        'actividades.required'                  => 'El :attribute es obligatorio',

        'actividades.*.mount.required'          => 'El :attribute es obligatorio',

        'n_dias_40.required'                    => 'El :attribute es obligatorio',

        'n_dias_100.required'                   => 'El :attribute es obligatorio',

        'observacion_pasajes.required'          => 'El :attribute es obligatorio',
    ];

    protected $customAttributes = [
        'fecha_inicio'          => 'fecha de inicio',
        'fecha_termino'         => 'fecha de término',
        'hora_salida'           => 'hora de salida',
        'hora_llegada'          => 'hora de llegada',
        'derecho_pago'          => 'derecho a pago',
        'motivos_cometido'      => 'motivo de cometido',
        'lugares_cometido'      => 'lugar de cometido',
        'actividad_realizada'   => 'actividad realizada',
        'medio_transporte'      => 'medio de transporte',
        'gastos_alimentacion'   => 'gastos de alimentación',
        'gastos_alojamiento'    => 'gastos de alojamiento',
        'actividades'           => 'actividades',
        'actividades.*.mount'   => 'monto',
        'actividades.*.rinde_gastos_servicio' => 'rinde gasto servicio',
        'n_dias_40'             => 'n° de días de alojamiento',
        'n_dias_100'            => 'n° de días diarios',
        'observacion_pasajes'   => 'observación de pasajes'
        // Agrega más nombres de atributos personalizados según sea necesario
    ];
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
                if ($request->motivos_cometido) {
                    $solicitud->motivos()->attach($request->motivos_cometido);
                }

                if ($request->lugares_cometido) {
                    $solicitud->lugares()->attach($request->lugares_cometido);
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

    public function storeSolicitudDelete(Request $request)
    {
        try {
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

            if ((isset($data['medio_transporte'])) && ($data['medio_transporte'])) {
                foreach ($data['medio_transporte'] as $value) {
                    if ($value === null) {
                        $data['medio_transporte'] = [];
                    }
                }
            }

            if ((isset($data['actividades'])) && ($data['actividades'])) {
                foreach ($data['actividades'] as $value) {
                    if ($value === null) {
                        $data['actividades'] = [];
                    }
                }
            }

            $validator = Validator::make($data, [
                'fecha_inicio'          => ['required', 'date', 'before_or_equal:fecha_termino'],
                'fecha_termino'         => ['required', 'date', 'after_or_equal:fecha_inicio'],
                'hora_llegada'          => ['required', 'after_or_equal:hora_salida'],
                'hora_salida'           => ['required', 'before_or_equal:hora_llegada'],
                'derecho_pago'          => ['required'],
                'motivos_cometido'      => ['required', 'array'],
                'lugares_cometido'      => ['required', 'array'],
                'actividad_realizada'   => ['required'],
                'medio_transporte'      => ['required', 'array'],
                'gastos_alimentacion'       => ['required'],
                'gastos_alojamiento'        => ['required'],
                'pernocta_lugar_residencia' => ['required'],
                'actividades'               => ['present', 'required', 'array'],
                'actividades.*.id'          => ['required'],
                'actividades.*.rinde_gasto' => ['required'],
                'actividades.*.mount' => [
                    function ($attribute, $value, $fail) {
                        $index          = preg_replace('/[^0-9]/', '', $attribute);
                        $rinde_gasto    = "actividades.{$index}.rinde_gasto";
                        if (request()->input($attribute) === null && request()->input($rinde_gasto) != 0) {
                            $fail("El monto es obligatorio");
                        }
                    },
                ],
                'actividades.*.rinde_gastos_servicio' => [
                    function ($attribute, $value, $fail) {
                        $medio_transporte   = request()->input('medio_transporte');
                        $index              = preg_replace('/[^0-9]/', '', $attribute);
                        $rinde_gasto        = "actividades.{$index}.rinde_gasto";
                        $id_actividad       = "actividades.{$index}.id";
                        $rinde_gasto_value  = request()->input($rinde_gasto);
                        $actividad_id_value = request()->input($id_actividad);
                        $rinde_gastos_servicio_value = request()->input($attribute);

                        if ((is_array($medio_transporte)) && (in_array(1, $medio_transporte) && $rinde_gasto_value != 1 && $actividad_id_value === 1 && $rinde_gastos_servicio_value === null)) {
                            $fail("Respuesta es obligatoria");
                        }
                    },
                ],
                'n_dias_40'                 => ['nullable'],
                'n_dias_100'                => ['nullable'],
                'observacion_gastos'        => ['nullable'],
                'archivos'                  => ['nullable'],
                'archivos_gastos'           => ['nullable'],
            ],  $this->customMessages, $this->customAttributes);


            if ($validator->fails()) {
                // Manejar los errores de validación aquí
                return response()->json($validator->errors(), 400);
            }

            $form = [
                'user_id',
                'fecha_inicio',
                'fecha_termino',
                'hora_llegada',
                'hora_salida',
                'derecho_pago',
                'actividad_realizada',
                'gastos_alimentacion',
                'gastos_alojamiento',
                'pernocta_lugar_residencia',
                'n_dias_40',
                'n_dias_100',
                'observacion_gastos'
            ];

            $solicitudData = Arr::only($data, $form); // Utilizar Arr::only() para filtrar las claves necesarias
            $solicitud = Solicitud::create($solicitudData);

            if ($solicitud) {
                if ($data['motivos_cometido']) {
                    $solicitud->motivos()->attach($data['motivos_cometido']);
                }

                if ($data['lugares_cometido']) {
                    $solicitud->lugares()->attach($data['lugares_cometido']);
                }

                if ($data['medio_transporte']) {
                    $solicitud->transportes()->attach($data['medio_transporte']);
                }
                if (isset($data['actividades'])) {
                    foreach ($data['actividades'] as $actividad) {
                        $id                         = (int)$actividad['id'];
                        $rinde_gasto                = (bool)$actividad['rinde_gasto'];
                        $mount                      = (int)$actividad['mount'];
                        $rinde_gastos_servicio      = (bool)$actividad['rinde_gastos_servicio'];
                        $solicitud->actividades()->attach(
                            $id,
                            [
                                'status'                => $rinde_gasto,
                                'mount'                 => $rinde_gasto ? $mount : null,
                                'status_admin'          => $rinde_gasto ? true : false,
                                'rinde_gastos_servicio' => $rinde_gastos_servicio
                            ]
                        );
                    }
                }

                if (isset($data['archivos'])) {
                    $files = $data['archivos'];
                    foreach ($files as $file) {
                        $new_files[] = [
                            'nombre'        => $file->getClientOriginalName(),
                            'size'          => $file->getSize(),
                            'format'        => $file->getMimeType(),
                            'solicitud_id'  => $solicitud->id,
                            'user_id'       => $solicitud->user_id,
                            'extension'     => $file->getClientOriginalExtension(),
                            'is_valid'      => $file->isValid()
                        ];
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

                if (isset($data['archivos_gastos'])) {
                    $files = $data['archivos_gastos'];
                    foreach ($files as $file) {
                        $new_files[] = [
                            'nombre'        => $file->getClientOriginalName(),
                            'size'          => $file->getSize(),
                            'format'        => $file->getMimeType(),
                            'solicitud_id'  => $solicitud->id,
                            'user_id'       => $solicitud->user_id,
                            'extension'     => $file->getClientOriginalExtension(),
                            'is_valid'      => $file->isValid()
                        ];
                        $fecha_solicitud    = Carbon::parse($solicitud->fecha_inicio);
                        $year               = $fecha_solicitud->format('Y');
                        $month              = $fecha_solicitud->format('m');
                        $fileName           = 'gastos/' . $solicitud->funcionario->rut . '/' . $year . '/' . $month . '/' . $file->getClientOriginalName();
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

    public function validateSolicitud(ValidateInformeSolicitudRequest $request)
    {
        try {
            $validate_date = $this->validateSolicitudDate($request);
            if ($validate_date) {
                $message = "Ya existe una solicitud en la fecha seleccionada.";
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
}
