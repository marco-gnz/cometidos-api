<?php

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

require __DIR__.'/auth.php';
