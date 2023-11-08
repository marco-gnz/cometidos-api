<?php

use App\Http\Controllers\Admin\Mantenedores\MantenedorController;
use App\Http\Controllers\Solicitud\SolicitudController;
use App\Http\Resources\UserAuthResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return response()->json(UserAuthResource::make($request->user()));
});

Route::group(
    [
        'namespace'     => 'Admin',
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::get('/admin/mantenedores/motivos', [MantenedorController::class, 'getMotivos']);
        Route::get('/admin/mantenedores/lugares', [MantenedorController::class, 'getLugares']);
        Route::get('/admin/mantenedores/transportes', [MantenedorController::class, 'getTransporte']);
        Route::get('/admin/mantenedores/actividades', [MantenedorController::class, 'getActividades']);
    }
);

Route::group(
    [
        'namespace'     => 'Solicitud',
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::post('/solicitud/store', [SolicitudController::class, 'storeSolicitud']);
        Route::post('/solicitud/store/validate', [SolicitudController::class, 'validateSolicitud']);
        Route::post('/solicitud/store/validate-file', [SolicitudController::class, 'validateFileSolicitud']);
    }
);
