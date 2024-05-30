<?php

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

Route::get('/test/store-contrato', function () {

    try {
        $users = User::all();

        foreach ($users as $user) {
           $data = [
                'ley_id'                => $user->ley_id,
                'estamento_id'          => $user->estamento_id,
                'grado_id'              => $user->grado_id,
                'cargo_id'              => $user->cargo_id,
                'departamento_id'       => $user->departamento_id,
                'sub_departamento_id'   => $user->sub_departamento_id,
                'establecimiento_id'    => $user->establecimiento_id,
                'hora_id'               => $user->hora_id,
                'calidad_id'            => $user->calidad_id,
           ];

            $contrato = new App\Models\Contrato($data);

            $user->contratos()->save($contrato);
        }
    } catch (\Exception $error) {
        Log::info($error->getMessage());
        return $error->getMessage();
    }
});

Route::get('/test/email', function () {

    try {
        $solicitud = App\Models\Solicitud::where('codigo', '10251202400009')->first();
        $emails = $solicitud->firmantes()->with('funcionario')->get()->pluck('funcionario.email')->toArray();

    return $emails;

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
