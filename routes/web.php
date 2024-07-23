<?php

use App\Models\Contrato;
use App\Models\EstadoInformeCometido;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\Grupo;
use App\Models\InformeCometido;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/convenio/{uuid}', [App\Http\Controllers\pdf\PdfController::class, 'showConvenio'])->name('convenio.show');
Route::get('/documento/{uuid}', [App\Http\Controllers\pdf\PdfController::class, 'showDocumento'])->name('documento.show');
Route::get('/gcf/{uuid}', [App\Http\Controllers\pdf\PdfController::class, 'showGastosCometidoFuncional'])->name('gastoscometidofuncional.show');
Route::get('/resolucion-cometido/{uuid}', [App\Http\Controllers\pdf\PdfController::class, 'showResolucionCometidoFuncional'])->name('resolucioncometidofuncional.show');
Route::get('/informe/{uuid}', [App\Http\Controllers\pdf\PdfController::class, 'showInformeCometido'])->name('informecometido.show');

Route::get('/email/test/{id}', function ($id) {
    switch ($id) {
        case 1:
            $data = Solicitud::orderBy('id', 'DESC')->first();
            return new \App\Mail\SolicitudCreated($data);
            break;

        case 2:
            $data = EstadoSolicitud::where('id', 1690)->orderBy('id', 'DESC')->first();
            return new \App\Mail\SolicitudChangeStatus($data->solicitud, $data, null);
            break;

        case 3:
            $data = InformeCometido::orderBy('id', 'DESC')->first();
            return new \App\Mail\InformeCometidoCreated($data);
            break;

        case 4:
            $data =
            EstadoInformeCometido::where('status', 1)->whereNotNull('observacion')->first();
            return new \App\Mail\InformeCometidoStatus($data);
            break;

        case 5:
            $data =
            ProcesoRendicionGasto::orderBy('id', 'DESC')->first();
            return new \App\Mail\ProcesoRendicionGastoCreated($data);
            break;

        case 6:
            $data =
            EstadoProcesoRendicionGasto::whereIn('status', [6])->orderBy('id', 'DESC')->first();
            return new \App\Mail\ProcesoRendicionGastoStatus($data);
            break;

        case 7:
            $data =
            EstadoProcesoRendicionGasto::whereIn('status', [2])->orderBy('id', 'DESC')->first();
            return new \App\Mail\ProcesoRendicionGastoStatus($data);
            break;
    }
});

Route::get('/test/grupos/codigos', function () {
    try {
        $grupos = Grupo::all();
        $codigo_incremental = 1;
        $codigo_asignado = [];

        foreach ($grupos as $key => $grupo) {
            // Genera el código inicial
            $codigo_base = (string) $codigo_incremental;
            $codigo = $codigo_base;

            // Verifica si el grupo ya tiene un código asignado
            if (isset($codigo_asignado[$grupo->id])) {
                // Si ya tiene un código asignado, úsalo
                $codigo = $codigo_asignado[$grupo->id];
            } else {
                // Busca duplicados con el mismo establecimiento_id, departamento_id y sub_departamento_id
                $duplicados = Grupo::where('establecimiento_id', $grupo->establecimiento_id)
                    ->where('departamento_id', $grupo->departamento_id)
                    ->where('sub_departamento_id', $grupo->sub_departamento_id)
                    ->orderBy('id', 'asc')
                    ->get();

                // Si hay duplicados, asignar el código con sufijos
                if ($duplicados->isNotEmpty()) {
                    $max_sufijo = 0;
                    foreach ($duplicados as $duplicado) {
                        if ($duplicado->id != $grupo->id) {
                            $codigo_asignado[$duplicado->id] = $codigo_base . '_' . ++$max_sufijo;
                        } else {
                            $codigo_asignado[$grupo->id] = $codigo_base;
                        }
                    }
                } else {
                    $codigo_asignado[$grupo->id] = $codigo_base;
                }
            }

            // Actualiza el registro con el nuevo código
            $grupo->codigo = $codigo_asignado[$grupo->id];
            $grupo->save();

            // Incrementa el código base para el próximo grupo
            $codigo_incremental++;
        }
    } catch (\Exception $error) {
        Log::info($error->getMessage());
        return $error->getMessage();
    }
});

Route::get('/test/sync-contratos/{id_establecimiento}', function ($id_establecimiento) {

    try {
        $contratos = Contrato::where('establecimiento_id', $id_establecimiento)->get();

        foreach ($contratos as $contrato) {
            $establecimiento_id  = $contrato->establecimiento_id;
            $departamento_id     = $contrato->departamento_id;
            $subDepartamento_id  = $contrato->sub_departamento_id;
            $user_id             = $contrato->user_id;

            $grupo = Grupo::where('establecimiento_id', $establecimiento_id)
                ->where('departamento_id', $departamento_id)
                ->where('sub_departamento_id', $subDepartamento_id)
                ->whereHas('firmantes', function ($query) {
                    $query->where('status', true);
                })
                ->whereDoesntHave('firmantes', function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                })
                ->first();

            if ($grupo) {
                $contrato->update([
                    'grupo_id'  => $grupo->id
                ]);
            }
        }
    } catch (\Exception $error) {
        Log::info($error->getMessage());
        return $error->getMessage();
    }
});

Route::get('/test/email', function () {

    try {
        $solicitud = App\Models\Solicitud::first();
        $emails = $solicitud->firmantes()->with('funcionario')->get()->pluck('funcionario.email')->toArray();

        $last_status = App\Models\EstadoSolicitud::where('is_reasignado', true)->whereNotNull('observacion')->orderBy('id', 'ASC')->first();
        return new \App\Mail\SolicitudUpdated($last_status->solicitud);
        $proceso_rendicion = App\Models\ProcesoRendicionGasto::first();
        $last_status_proceso = App\Models\EstadoProcesoRendicionGasto::find(249);
        $informe = App\Models\InformeCometido::first();
        $last_status_informe = App\Models\EstadoInformeCometido::first();
        /* return App\Events\SolicitudUpdated::dispatch($solicitud); */
        return new \App\Mail\InformeCometidoStatus($last_status_informe);
    } catch (\Exception $error) {
        Log::info($error->getMessage());
        return $error->getMessage();
    }
});

Route::get('/test/check-plazo-informe', function () {
    // Fecha de habilitación de informe de cometido (cuando termina el cometido)
    $fecha_termino_cometido = "2024-07-05 17:00:00";
    $fecha_termino_cometido = Carbon::parse($fecha_termino_cometido);
    $fecha_termino_cometido_copy     = $fecha_termino_cometido->copy();

    // Fecha de ingreso de cometido
    $fecha_ingreso_informe          = Carbon::parse("2024-07-09 17:10:00");


    // Fines de semana entre fecha de termino de cometido y fecha de ingreso cometido
    $fds = 0;
    while ($fecha_termino_cometido->lte($fecha_ingreso_informe)) {
        if ($fecha_termino_cometido->isSaturday() || $fecha_termino_cometido->isSunday()) {
            $fds++;
        }
        $fecha_termino_cometido->addDay();
    }
    $feriados = 0;

    //obtener el plazo que hay para ingresar informe
    $plazo_dias_ingreso_informe         = 2 + $fds + $feriados;
    $plazo_total_dias_ingreso_informe   = $plazo_dias_ingreso_informe;

    $plazo  = $fecha_termino_cometido_copy->addDays($plazo_total_dias_ingreso_informe);

    Log::info($plazo);
    if ($fecha_ingreso_informe->lte($plazo)) {
        return "STATUS_INGRESO_EN_PLAZO";
    } else {
        return "STATUS_INGRESO_TARDIO";
    }
});

require __DIR__ . '/auth.php';
