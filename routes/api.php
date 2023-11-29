<?php

use App\Http\Controllers\Admin\Grupos\GrupoFirmaController;
use App\Http\Controllers\Admin\Mantenedores\MantenedorController;
use App\Http\Controllers\Admin\Rendicion\ProcesoRendicionController;
use App\Http\Controllers\Admin\Solicitudes\SolicitudAdminController;
use App\Http\Controllers\File\FileController;
use App\Http\Controllers\Rendicion\RendicionController;
use App\Http\Controllers\Solicitud\SolicitudController;
use App\Http\Resources\UserAuthResource;
use App\Models\Solicitud;
use App\Models\User;
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

Route::get('/tokens', function(){
    $users = User::get();

    foreach ($users as $user) {
        $user->update([
            'password'  => bcrypt($user->rut)
        ]);
        $user->createToken('sa');
    }
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
        Route::get('/admin/mantenedores/establecimientos', [MantenedorController::class, 'getEstablecimientos']);
        Route::get('/admin/mantenedores/departamentos', [MantenedorController::class, 'getDepartamentos']);
        Route::get('/admin/mantenedores/subdepartamentos', [MantenedorController::class, 'getSubdepartamentos']);
        Route::get('/admin/mantenedores/roles', [MantenedorController::class, 'getRoles']);
        Route::get('/admin/mantenedores/firmantes', [MantenedorController::class, 'getFirmantes']);
        Route::get('/admin/mantenedores/user/{id}', [MantenedorController::class, 'getUser']);

        Route::post('/admin/grupos', [GrupoFirmaController::class, 'storeGrupo']);
        Route::get('/admin/grupos', [GrupoFirmaController::class, 'listGruposFirma']);
        Route::get('/admin/grupos/{uuid}', [GrupoFirmaController::class, 'findGrupoFirma']);

        Route::get('/admin/solicitudes', [SolicitudAdminController::class, 'listSolicitudes']);
        Route::get('/admin/solicitudes/{uuid}/{nav}', [SolicitudAdminController::class, 'findSolicitud']);

        Route::get('/admin/rendicion/list', [ProcesoRendicionController::class, 'getProcesoRendiciones']);
        Route::get('/admin/rendicion/{uuid}', [ProcesoRendicionController::class, 'getProcesoRendicion']);
        Route::put('/admin/rendicion/{uuid}', [ProcesoRendicionController::class, 'statusRendicion']);
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

        Route::get('/rendicion/solicitudes', [RendicionController::class, 'solicitudesRendicionGastos']);
        Route::post('/rendicion', [RendicionController::class, 'storeRendicion']);
        Route::get('/rendicion/list', [RendicionController::class, 'getProcesoRendiciones']);
        Route::get('/rendicion/{uuid}', [RendicionController::class, 'getProcesoRendicion']);
    }
);

Route::group(
    [
        'namespace'     => 'Documento',
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::post('/documento/download-file/{uuid}', [FileController::class, 'downloadFile']);
        Route::post('/documento/validate-file', [FileController::class, 'validateFileSolicitud']);
        Route::post('/documento/upload-file', [FileController::class, 'uploadFile']);
        Route::delete('/documento/delete-file/{uuid}', [FileController::class, 'deleteFile']);
    }
);
