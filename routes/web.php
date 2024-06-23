<?php

use App\Models\Contrato;
use App\Models\Grupo;
use App\Models\User;
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

Route::get('/test/sync-contratos', function () {

    try {
        $contratos = Contrato::all();

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

        $last_status = App\Models\EstadoSolicitud::where('is_reasignado', true)->orderBy('id', 'DESC')->first();
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
require __DIR__ . '/auth.php';
