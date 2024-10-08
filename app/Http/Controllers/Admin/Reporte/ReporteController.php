<?php

namespace App\Http\Controllers\Admin\Reporte;

use App\Exports\SolicitudesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reporte\SolicitudReporteCountRequest;
use App\Http\Requests\Reporte\SolicitudReporteRequest;
use App\Jobs\CreateSolicitudExportFile;
use App\Mail\ExportFailedNotification;
use App\Mail\SolicitudExportMail;
use App\Models\Solicitud;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function columnsReporte()
    {
        try {
            $this->authorize('export', Solicitud::class);
            $columns_cometido = $this->columnsCometido();
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'columns'       => $columns_cometido
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function countRegistros(SolicitudReporteCountRequest $request)
    {
        try {
            $this->authorize('export', Solicitud::class);

            $solicitudes = $this->registerAction($request);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'total'         => count($solicitudes)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    private function registerAction($request)
    {
        $solicitudes = Solicitud::whereYear('fecha_inicio', $request->year)
            ->where(DB::raw('MONTH(fecha_inicio)'), $request->month)
            ->derechoViatico($request->derecho_viatico)
            ->isLoadSirh($request->is_sirh)
            ->tipoComision($request->tipo_cometido)
            ->jornada($request->jornada_cometido)
            ->medioTransporte($request->medios_transporte)
            ->motivo($request->motivo_cometido)
            ->estado($request->estado)
            ->estadoInformeCometido($request->estado_informe)
            ->establecimiento($request->establecimiento_id)
            ->departamento($request->depto_id)
            ->subdepartamento($request->subdepto_id)
            ->ley($request->ley_id)
            ->estamento($request->estamento_id)
            ->calidad($request->calidad_id);

        $this->filterRole($solicitudes, $request);

        return $solicitudes->get();
    }

    public function export(SolicitudReporteRequest $request)
    {
        try {
            $email_auth     = Auth::user();
            $folder         = now()->toDateString() . '-' . str_replace(':', '-', now()->toTimeString());
            $solicitudes    = $this->registerAction($request);

            $filter_all = (object) [
                'solicitudes'       => (bool)$request->solicitudes,
                'informes_cometido' => (bool)$request->informes_cometido,
                'valorizacion'      => (bool)$request->valorizacion,
            ];
            $batches = [
                new CreateSolicitudExportFile($folder, $request->columns, $solicitudes, $filter_all)
            ];

            Bus::batch($batches)
                ->name('Export Solicitudes')
                ->then(function (Batch $batch) use ($folder) {
                    $path = "exports/{$folder}/solicitudes.xlsx";
                    $file = storage_path("app/{$folder}/solicitudes.xlsx");
                    if (file_exists($file)) {
                        Storage::disk('public')->put($path, file_get_contents($file));
                    }
                })
                ->catch(function (Batch $batch, Throwable $e) use ($email_auth, $folder) {
                    Log::info("Error en la exportación: " . $e->getMessage());

                    $file = storage_path("app/{$folder}/solicitudes.xlsx");
                    if (file_exists($file)) {
                        if ($email_auth) {
                            Mail::to($email_auth->email)->send(new SolicitudExportMail($file));
                        }
                        Storage::disk('local')->delete("{$folder}/solicitudes.xlsx");
                    }
                    Storage::disk('local')->deleteDirectory($folder);
                    if ($email_auth) {
                        Mail::to($email_auth->email)->send(new ExportFailedNotification($e->getMessage()));
                    }
                })
                ->finally(function (Batch $batch) use ($folder, $email_auth) {
                    $file = storage_path("app/{$folder}/solicitudes.xlsx");
                    if (file_exists($file)) {
                        if ($email_auth) {
                            Mail::to($email_auth->email)->send(new SolicitudExportMail($file));
                        }
                        Storage::disk('local')->delete("{$folder}/solicitudes.xlsx");
                        Storage::disk('local')->deleteDirectory($folder);
                    }
                })
                ->dispatch();

            return response()->json(
                [
                    'status'        => 'success',
                    'title'         => 'Generando archivo',
                    'message'       => 'La exportación está en proceso. Recibirás el archivo en tu correo.',
                ],
                200
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    private function filterRole($query, $request)
    {
        $auth                   = Auth::user();
        $establecimientos_id    = $auth->establecimientos->pluck('id')->toArray();
        $leyes_id               = $auth->leyes->pluck('id')->toArray();
        $deptos_id              = $auth->departamentos->pluck('id')->toArray();
        $transportes_id         = $auth->transportes->pluck('id')->toArray();
        $tip_comision_id        = $auth->tipoComisiones->pluck('id')->toArray();

        if ($establecimientos_id && !$request->establecimiento_id) {
            $query->whereHas('establecimiento', function ($q) use ($establecimientos_id) {
                $q->whereIn('id', $establecimientos_id);
            });
        }

        if ($leyes_id && !$request->ley_id) {
            $query->whereHas('ley', function ($q) use ($leyes_id) {
                $q->whereIn('id', $leyes_id);
            });
        }

        if ($transportes_id && !$request->medios_transporte) {
            $query->whereHas('transportes', function ($q) use ($transportes_id) {
                $q->whereIn('transportes.id', $transportes_id);
            });
        }

        if ($tip_comision_id && !$request->tipo_cometido) {
            $query->whereHas('tipoComision', function ($q) use ($tip_comision_id) {
                $q->whereIn('id', $tip_comision_id);
            });
        }

        if ($deptos_id && !$request->depto_id) {
            $query->whereHas('departamento', function ($q) use ($deptos_id) {
                $q->whereIn('id', $deptos_id);
            });
        }
    }

    private function columnsCometido()
    {
        $solicitud      = 'solicitud';
        $informe        = 'informe';
        $valorizacion   = 'valorizacion';
        return [
            $solicitud =>
            [
                (object) [
                    'nombre'    => 'N° resolución',
                    'campo'     => 'codigo',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'N° resolución SIRH',
                    'campo'     => 'codigo_sirh',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Rut funcionario',
                    'campo'     => 'funcionario.rut_completo',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Nombre funcionario',
                    'campo'     => 'funcionario.nombre_completo',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Correo electrónico funcionario',
                    'campo'     => 'funcionario.email',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Rut Jefatura Directa',
                    'campo'     => 'jefatura_directa_rut',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Nombres Jefatura Directa',
                    'campo'     => 'jefatura_directa_nombres',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Correo electrónico Jefatura Directa',
                    'campo'     => 'jefatura_directa_email',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Fecha de inicio',
                    'campo'     => 'fecha_inicio',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Fecha de término',
                    'campo'     => 'fecha_termino',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Hora de llegada',
                    'campo'     => 'hora_llegada',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Hora de salida',
                    'campo'     => 'hora_salida'
                ],
                (object) [
                    'nombre'    => 'Derecho a viático',
                    'campo'     => 'derecho_pago',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Utiliza transporte',
                    'campo'     => 'utiliza_transporte',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Transportes utilizados',
                    'campo'     => 'transportes.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Viaja con acompañante',
                    'campo'     => 'viaja_acompaniante',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Jornada',
                    'campo'     => 'jornada',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Afecta a convenio',
                    'campo'     => 'afecta_convenio',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Alimentación en la Red',
                    'campo'     => 'alimentacion_red',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Gastos de alimentación',
                    'campo'     => 'gastos_alimentacion',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Gastos de alojamiento',
                    'campo'     => 'gastos_alojamiento',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Pernocta fuera',
                    'campo'     => 'pernocta_lugar_residencia',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'N° días al 40%',
                    'campo'     => 'n_dias_40',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'N° días al 100%',
                    'campo'     => 'n_dias_100',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Actividad realizada',
                    'campo'     => 'actividad_realizada',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Observación',
                    'campo'     => 'observacion',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Observación en gastos',
                    'campo'     => 'observacion_gastos',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Total días cometido',
                    'campo'     => 'total_dias_cometido',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Departamento',
                    'campo'     => 'departamento.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Subdepartamento',
                    'campo'     => 'subdepartamento.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Ley',
                    'campo'     => 'ley.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Calidad',
                    'campo'     => 'calidad.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Grado',
                    'campo'     => 'grado.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Estamento',
                    'campo'     => 'estamento.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Establecimiento',
                    'campo'     => 'establecimiento.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Tipo de comisión',
                    'campo'     => 'tipoComision.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Cargo',
                    'campo'     => 'cargo.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Horas',
                    'campo'     => 'hora.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Ítem presupuestario',
                    'campo'     => 'itemPresupuestario.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Estado solicitud',
                    'campo'     => 'status',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Motivo de cometido',
                    'campo'     => 'motivos.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Lugar de cometido',
                    'campo'     => 'lugares.nombre',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Estado carga SIRH',
                    'campo'     => 'load_sirh',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Fecha ingreso cometido',
                    'campo'     => 'fecha_by_user',
                    'code'      => $solicitud
                ],
                (object) [
                    'nombre'    => 'Firmas del cometido (Tipo de firma_Fecha de firma)',
                    'campo'     => 'firmas',
                    'code'      => $solicitud
                ],
            ],
            $informe =>
            [
                (object) [
                    'nombre'    => 'Código de informe',
                    'campo'     => 'informe_cometido_codigo',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Estado de ingreso',
                    'campo'     => 'informe_cometido_estado',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Fecha inicio',
                    'campo'     => 'informe_cometido_fecha_inicio',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Fecha término',
                    'campo'     => 'informe_cometido_fecha_termino',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Hora de llegada',
                    'campo'     => 'informe_cometido_hora_llegada',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Hora de salida',
                    'campo'     => 'informe_cometido_hora_salida',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Actividad realizada',
                    'campo'     => 'informe_cometido_actividad_realizada',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Utiliza transporte',
                    'campo'     => 'informe_cometido_utiliza_transporte',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Transportes utilizados',
                    'campo'     => 'informe_cometido_transportes',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Estado informe',
                    'campo'     => 'informe_cometido_estado_informe',
                    'code'      => $informe
                ],
                (object) [
                    'nombre'    => 'Fecha ingreso',
                    'campo'     => 'informe_cometido_created_at',
                    'code'      => $informe
                ],
            ],
            $valorizacion =>
            [
                (object) [
                    'nombre'    => 'Fecha inicio vigencia escala',
                    'campo'     => 'valorizacion_fecha_inicio_escala',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Fecha término vigencia escala',
                    'campo'     => 'valorizacion_fecha_termino_escala',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Grado escala',
                    'campo'     => 'valorizacion_grado_escala',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Ley escala',
                    'campo'     => 'valorizacion_ley_escala',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Valor día 40% escala',
                    'campo'     => 'valorizacion_valor_dia_40_escala',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Valor día 100% escala',
                    'campo'     => 'valorizacion_valor_dia_100_escala',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'N° días al 40%',
                    'campo'     => 'valorizacion_n_dias_40',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'N° días al 100%',
                    'campo'     => 'valorizacion_n_dias_100',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Total valorización calculado',
                    'campo'     => 'valorizacion_monto_total',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'N° días ajustes al 40%',
                    'campo'     => 'valorizacion_n_dias_ajustes_40',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'N° días ajustes al 100%',
                    'campo'     => 'valorizacion_n_dias_ajustes_100',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Monto ajustes al 40%',
                    'campo'     => 'valorizacion_monto_ajustes_40',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Monto ajustes al 100%',
                    'campo'     => 'valorizacion_monto_ajustes_100',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'Total monto en ajustes',
                    'campo'     => 'valorizacion_monto_ajustes',
                    'code'      => $valorizacion
                ],
                (object) [
                    'nombre'    => 'TOTAL VALORIZACION COMETIDO',
                    'campo'     => 'valorizacion_total',
                    'code'      => $valorizacion
                ],
            ]
        ];
    }
}
