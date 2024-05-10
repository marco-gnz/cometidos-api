<?php

use App\Http\Controllers\Admin\Ausentismo\AusentismoController;
use App\Http\Controllers\Admin\Conceptos\ConceptosController;
use App\Http\Controllers\Admin\Grupos\GrupoFirmaController;
use App\Http\Controllers\Admin\Mantenedores\MantenedorController;
use App\Http\Controllers\Admin\Reasignacion\ReasignacionController;
use App\Http\Controllers\Admin\Rendicion\ProcesoRendicionController;
use App\Http\Controllers\Admin\Solicitudes\SolicitudAdminController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\File\FileController;
use App\Http\Controllers\Rendicion\RendicionController;
use App\Http\Controllers\Solicitud\SolicitudController;
use App\Http\Controllers\User\Archivos\ArchivosController;
use App\Http\Controllers\User\Ausentismos\AusentismosController;
use App\Http\Controllers\User\Cuenta\CuentaController;
use App\Http\Controllers\User\Firmantes\FirmantesController;
use App\Http\Controllers\User\Solicitudes\SolicitudesController;
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

Route::get('/tokens', function () {
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
        Route::get('/admin/mantenedores/actividades/{uuid}', [MantenedorController::class, 'getActividades']);
        Route::get('/admin/mantenedores/establecimientos', [MantenedorController::class, 'getEstablecimientos']);
        Route::get('/admin/mantenedores/departamentos', [MantenedorController::class, 'getDepartamentos']);
        Route::get('/admin/mantenedores/subdepartamentos', [MantenedorController::class, 'getSubdepartamentos']);
        Route::get('/admin/mantenedores/roles', [MantenedorController::class, 'getRoles']);
        Route::get('/admin/mantenedores/firmantes', [MantenedorController::class, 'getFirmantes']);
        Route::get('/admin/mantenedores/user/{id}', [MantenedorController::class, 'getUser']);
        Route::get('/admin/mantenedores/estados-rechazo', [MantenedorController::class, 'getStatusRechazo']);
        Route::get('/admin/mantenedores/estados-cometido', [MantenedorController::class, 'getStatusCometido']);
        Route::get('/admin/mantenedores/tipo-comisiones', [MantenedorController::class, 'getTipoComisiones']);
        Route::get('/admin/mantenedores/jornadas-cometido', [MantenedorController::class, 'getJornadasCometido']);
        Route::get('/admin/mantenedores/paises', [MantenedorController::class, 'getPaises']);

        Route::post('/admin/grupos', [GrupoFirmaController::class, 'storeGrupo']);
        Route::post('/admin/grupos/change-positions', [GrupoFirmaController::class, 'changePosition']);
        Route::get('/admin/grupos', [GrupoFirmaController::class, 'listGruposFirma']);
        Route::get('/admin/grupos/{uuid}', [GrupoFirmaController::class, 'findGrupoFirma']);
        Route::delete('/admin/grupos/delete/{uuid}', [GrupoFirmaController::class, 'deleteGrupo']);
        Route::delete('/admin/grupos/delete-firma/{uuid}', [GrupoFirmaController::class, 'deleteFirmante']);
        Route::post('/admin/grupos/store-firmante', [GrupoFirmaController::class, 'storeFirmanteGrupo']);

        Route::get('/admin/solicitudes', [SolicitudAdminController::class, 'listSolicitudes']);
        Route::get('/admin/solicitudes/{uuid}/{nav}', [SolicitudAdminController::class, 'findSolicitud']);
        Route::put('/admin/solicitudes/{uuid}/fijada', [SolicitudAdminController::class, 'solicitudFijada']);
        Route::put('/admin/solicitudes/reasignar-firma', [SolicitudAdminController::class, 'reasignarFirmaSolicitud']);
        Route::post('/admin/solicitudes/show-calculo', [SolicitudAdminController::class, 'propuestaCalculo']);
        Route::put('/admin/solicitudes/aplicar-calculo/{uuid}', [SolicitudAdminController::class, 'aplicarCalculo']);
        Route::put('/admin/solicitudes/status', [SolicitudAdminController::class, 'actionStatusSolicitud']);
        Route::post('/admin/solicitudes/status/check', [SolicitudAdminController::class, 'checkActionFirma']);
        Route::post('/admin/solicitudes/update-convenio', [SolicitudAdminController::class, 'updateConvenio']);
        Route::put('/admin/solicitudes/status/firmante/{uuid}', [SolicitudAdminController::class, 'updateStatusFirmante']);
        Route::post('/admin/solicitudes/sync-grupo', [SolicitudAdminController::class, 'syncGrupoSolicitud']);

        Route::get('/admin/rendicion/list', [ProcesoRendicionController::class, 'getProcesoRendiciones']);
        Route::get('/admin/rendicion/{uuid}', [ProcesoRendicionController::class, 'getProcesoRendicion']);
        Route::put('/admin/rendicion/{uuid}', [ProcesoRendicionController::class, 'statusRendicion']);
        Route::post('/admin/rendicion/update-pago', [ProcesoRendicionController::class, 'updatePago']);

        Route::post('/admin/solicitudes/store-ajuste-calculo', [SolicitudAdminController::class, 'storeAjuste']);
        Route::get('/admin/solicitudes/preview-ajuste-calculo', [SolicitudAdminController::class, 'previewAjuste']);
        Route::delete('/admin/solicitudes/delete-ajuste-calculo/{uuid}', [SolicitudAdminController::class, 'deleteAjuste']);

        Route::get('/admin/ausentismo/list', [AusentismoController::class, 'listAusentismos']);
        Route::get('/admin/ausentismo/tota-solicitudes', [AusentismoController::class, 'getSolicitudesDate']);
        Route::post('/admin/ausentismo/store', [AusentismoController::class, 'storeAusentismo']);
        Route::delete('/admin/ausentismo/{uuid}', [AusentismoController::class, 'deleteAusentismo']);

        Route::post('/admin/reasignacion/store', [ReasignacionController::class, 'storeReasignacion']);
        Route::get('/admin/reasignacion/list', [ReasignacionController::class, 'listReasignaciones']);
        Route::delete('/admin/reasignacion/{uuid}', [ReasignacionController::class, 'deleteReasignacion']);

        Route::post('/admin/concepto/store-user', [ConceptosController::class, 'storeUser']);
        Route::post('/admin/concepto/get-users', [ConceptosController::class, 'getUsersConcepto']);
        Route::get('/admin/concepto/list', [ConceptosController::class, 'listConceptos']);
        Route::post('/admin/concepto/change-position', [ConceptosController::class, 'changePosition']);
        Route::post('/admin/concepto/delete-user', [ConceptosController::class, 'deleteUser']);
    }
);

Route::group(
    [
        'namespace'     => 'Solicitud',
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::post('/solicitud/get-count-convenios', [SolicitudController::class, 'getCountConvenios']);

        Route::get('/archivos/list', [ArchivosController::class, 'listArchivos']);
        Route::get('/firmantes/list', [FirmantesController::class, 'listFirmantes']);

        Route::get('/solicitud/list', [SolicitudesController::class, 'listSolicitudes']);
        Route::post('/solicitud/anular', [SolicitudesController::class, 'anularSolicitud']);
        Route::get('/solicitud/{uuid}/{type}', [SolicitudesController::class, 'getSolicitud']);
        Route::post('/solicitud/store', [SolicitudController::class, 'storeSolicitud']);
        Route::post('/solicitud/store/validate', [SolicitudController::class, 'validateSolicitud']);
        Route::post('/solicitud/store/solicitud-dates', [SolicitudController::class, 'datesSolicitudInCalendar']);

        Route::get('/solicitud/update/show/{uuid}', [SolicitudController::class, 'getSolicitudUpdate']);
        Route::post('/solicitud/update/validate', [SolicitudController::class, 'validateUpdateSolicitud']);
        Route::post('/solicitud/update', [SolicitudController::class, 'updateSolicitud']);

        Route::get('/rendicion/solicitudes', [RendicionController::class, 'solicitudesRendicionGastos']);
        Route::post('/rendicion', [RendicionController::class, 'storeRendicion']);
        Route::post('/rendicion/update', [RendicionController::class, 'updateRendicion']);
        Route::post('/rendicion/anular', [RendicionController::class, 'anularRendicion']);
        Route::post('/rendicion/aprobar', [RendicionController::class, 'aprobarRendicion']);
        Route::delete('/rendicion/{uuid}', [RendicionController::class, 'deleteRendicion']);
        Route::get('/rendicion/list', [RendicionController::class, 'getProcesoRendiciones']);
        Route::get('/rendicion/{uuid}', [RendicionController::class, 'getProcesoRendicion']);
        Route::get('/rendicion/update/{uuid}', [RendicionController::class, 'getProcesoRendicionUpdate']);

        Route::post('/informe/store', [SolicitudController::class, 'storeInformeCometido']);
        Route::post('/informe/status', [SolicitudController::class, 'statusInformeCometido']);

        Route::get('/ausentismo/list', [AusentismosController::class, 'listAusentismos']);
        Route::post('/ausentismo/store', [AusentismosController::class, 'storeAusentismo']);
        Route::delete('/ausentismo/{uuid}', [AusentismosController::class, 'deleteAusentismo']);
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

Route::group(
    [
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::post('/change-pass/{uuid}', [NewPasswordController::class, 'changePass']);
        Route::post('/change-data', [CuentaController::class, 'changeData']);
    }
);
