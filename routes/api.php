<?php

use App\Http\Controllers\Admin\Ausentismo\AusentismoController;
use App\Http\Controllers\Admin\Conceptos\ConceptosController;
use App\Http\Controllers\Admin\Configuration\ConfigurationController;
use App\Http\Controllers\Admin\Convenios\ConvenioController;
use App\Http\Controllers\Admin\Grupos\GrupoFirmaController;
use App\Http\Controllers\Admin\Mantenedores\MantenedorAdminController;
use App\Http\Controllers\Admin\Mantenedores\MantenedorController;
use App\Http\Controllers\Admin\Perfil\PerfilController;
use App\Http\Controllers\Admin\Reasignacion\ReasignacionController;
use App\Http\Controllers\Admin\Rendicion\ProcesoRendicionController;
use App\Http\Controllers\Admin\Reporte\ReporteController;
use App\Http\Controllers\Admin\Solicitudes\SolicitudAdminController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\File\FileController;
use App\Http\Controllers\LinksController;
use App\Http\Controllers\Rendicion\RendicionController;
use App\Http\Controllers\Solicitud\SolicitudController;
use App\Http\Controllers\User\Archivos\ArchivosController;
use App\Http\Controllers\User\Ausentismos\AusentismosController;
use App\Http\Controllers\User\Cuenta\CuentaController;
use App\Http\Controllers\User\DocumentoInstitucional\DocumentoInstitucionalController;
use App\Http\Controllers\User\Firmantes\FirmantesController;
use App\Http\Controllers\User\Solicitudes\SolicitudesController;
use App\Http\Resources\UserAuthResource;
use App\Models\Lugar;
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
        Route::post('/admin/mantenedores/permisos-adicionales', [MantenedorController::class, 'getPermisosAdicionales']);
        Route::get('/admin/mantenedores/ilustres', [MantenedorController::class, 'getIlustres']);
        Route::get('/admin/mantenedores/leys', [MantenedorController::class, 'getLeys']);
        Route::get('/admin/mantenedores/leys/user', [MantenedorController::class, 'getLeysUser']);
        Route::get('/admin/mantenedores/grados', [MantenedorController::class, 'getGrados']);
        Route::get('/admin/mantenedores/motivos', [MantenedorController::class, 'getMotivos']);
        Route::get('/admin/mantenedores/lugares', [MantenedorController::class, 'getLugares']);
        Route::get('/admin/mantenedores/transportes', [MantenedorController::class, 'getTransporte']);
        Route::get('/admin/mantenedores/transportes/user', [MantenedorController::class, 'getTransporteUser']);
        Route::get('/admin/mantenedores/actividades/{uuid}', [MantenedorController::class, 'getActividades']);
        Route::get('/admin/mantenedores/establecimientos', [MantenedorController::class, 'getEstablecimientos']);
        Route::get('/admin/mantenedores/establecimientos/user', [MantenedorController::class, 'getEstablecimientosUser']);
        Route::get('/admin/mantenedores/departamentos', [MantenedorController::class, 'getDepartamentos']);
        Route::get('/admin/mantenedores/departamentos/user', [MantenedorController::class, 'getDepartamentosUser']);
        Route::get('/admin/mantenedores/subdepartamentos', [MantenedorController::class, 'getSubdepartamentos']);
        Route::get('/admin/mantenedores/departamentos-to-group/{establecimiento_id}', [MantenedorController::class, 'getDepartamentosToGroup']);
        Route::get('/admin/mantenedores/subdepartamentos-to-group/{establecimiento_id}/{departamento_id}', [MantenedorController::class, 'getSubdepartamentosToGroup']);
        Route::get('/admin/mantenedores/roles', [MantenedorController::class, 'getRoles']);
        Route::get('/admin/mantenedores/roles/perfil', [MantenedorController::class, 'getRolesPerfil']);
        Route::get('/admin/mantenedores/roles/users-especial/{concepto_uuid}/{establecimiento_id}', [MantenedorController::class, 'getRolesUsuariosEspecial']);
        Route::get('/admin/mantenedores/firmantes', [MantenedorController::class, 'getFirmantes']);
        Route::get('/admin/mantenedores/user/{id}', [MantenedorController::class, 'getUser']);
        Route::get('/admin/mantenedores/estados-rechazo', [MantenedorController::class, 'getStatusRechazo']);
        Route::get('/admin/mantenedores/estados-cometido', [MantenedorController::class, 'getStatusCometido']);
        Route::get('/admin/mantenedores/estados-informe', [MantenedorController::class, 'getStatusInforme']);
        Route::get('/admin/mantenedores/estados-rendicion', [MantenedorController::class, 'getStatusRendicion']);
        Route::get('/admin/mantenedores/tipo-comisiones', [MantenedorController::class, 'getTipoComisiones']);
        Route::get('/admin/mantenedores/tipo-comisiones/user', [MantenedorController::class, 'getTipoComisionesUser']);
        Route::get('/admin/mantenedores/jornadas-cometido', [MantenedorController::class, 'getJornadasCometido']);
        Route::get('/admin/mantenedores/paises', [MantenedorController::class, 'getPaises']);
        Route::get('/admin/mantenedores/conceptos-pres', [MantenedorController::class, 'getConceptos']);
        Route::get('/admin/mantenedores/datos-bancarios', [MantenedorController::class, 'getDatosBancarios']);

        Route::get('/admin/mantenedores/estamentos', [MantenedorController::class, 'getEstamentos']);
        Route::get('/admin/mantenedores/cargos', [MantenedorController::class, 'getCargos']);
        Route::get('/admin/mantenedores/calidad', [MantenedorController::class, 'getCalidad']);
        Route::get('/admin/mantenedores/horas', [MantenedorController::class, 'getHoras']);

        Route::post('/admin/grupos', [GrupoFirmaController::class, 'storeGrupo']);
        Route::post('/admin/grupos/change-positions', [GrupoFirmaController::class, 'changePosition']);
        Route::get('/admin/grupos', [GrupoFirmaController::class, 'listGruposFirma']);
        Route::get('/admin/grupos/{uuid}', [GrupoFirmaController::class, 'findGrupoFirma']);
        Route::delete('/admin/grupos/delete/{uuid}', [GrupoFirmaController::class, 'deleteGrupo']);
        Route::delete('/admin/grupos/delete-firma/{uuid}', [GrupoFirmaController::class, 'deleteFirmante']);
        Route::post('/admin/grupos/store-firmante', [GrupoFirmaController::class, 'storeFirmanteGrupo']);

        Route::get('/admin/users', [UserController::class, 'listUsers']);
        Route::post('/admin/users/store', [UserController::class, 'storeUser']);
        Route::get('/admin/users/{uuid}', [UserController::class, 'getUser']);
        Route::get('/admin/users/update/{uuid}', [UserController::class, 'getUserUpdate']);
        Route::put('/admin/users/update/{uuid}', [UserController::class, 'userUpdate']);
        Route::put('/admin/users/cuenta-bancaria/status/{uuid}', [UserController::class, 'updateStatusCuentaBancaria']);
        Route::post('/admin/users/cuenta-bancaria/store', [UserController::class, 'storeCuentaBancaria']);
        Route::put('/admin/users/status/{uuid}', [UserController::class, 'updateStatusUser']);
        Route::put('/admin/users/status-permiso', [UserController::class, 'updatePermisoPrincipalUser']);
        Route::delete('/admin/users/contrato/{uuid}', [UserController::class, 'deleteContrato']);
        Route::post('/admin/users/contrato', [UserController::class, 'storeContrato']);
        Route::put('/admin/users/contrato/{uuid}', [UserController::class, 'updateContrato']);
        Route::post('/admin/users/contrato/change-grupo', [UserController::class, 'updateGrupoContrato']);

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
        Route::put('/admin/solicitudes/load-sirh/{uuid}', [SolicitudAdminController::class, 'checkLoadSirh']);

        Route::get('/admin/rendicion/list', [ProcesoRendicionController::class, 'getProcesoRendiciones']);
        Route::post('/admin/rendicion/status', [ProcesoRendicionController::class, 'statusProcesoRenicion']);
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

        Route::get('/admin/configurations/{establecimiento_id}', [ConfigurationController::class, 'getConfiguration']);
        Route::put('/admin/configurations/{configuration_id}', [ConfigurationController::class, 'updateConfiguration']);

        Route::get('/admin/convenios', [ConvenioController::class, 'getConvenios']);
        Route::post('/admin/convenios', [ConvenioController::class, 'storeConvenio']);
        Route::get('/admin/convenios/{uuid}', [ConvenioController::class, 'getConvenio']);
        Route::get('/admin/convenios/{uuid}/{year}', [ConvenioController::class, 'getCometidosConvenio']);
        Route::get('/admin/convenios/find/edit/{uuid}', [ConvenioController::class, 'getConvenioEdit']);
        Route::delete('/admin/convenios/{uuid}', [ConvenioController::class, 'deleteConvenio']);
        Route::put('/admin/convenios/{uuid}', [ConvenioController::class, 'updateConvenio']);
        Route::put('admin/convenios/status/{uuid}', [ConvenioController::class, 'updateConvenioStatus']);
        Route::get('/admin/con-users', [ConvenioController::class, 'getUsers']);

        Route::post('/admin/perfil', [PerfilController::class, 'storePerfil']);
        Route::get('/admin/perfil', [PerfilController::class, 'getPerfiles']);
        Route::get('/admin/perfil/edit/{uuid}', [PerfilController::class, 'getPerfilEdit']);
        Route::delete('/admin/perfil/{uuid}', [PerfilController::class, 'deletePerfil']);
        Route::put('/admin/perfil/{uuid}', [PerfilController::class, 'updatePerfil']);

        Route::get('/admin/reporte/count', [ReporteController::class, 'countRegistros']);
        Route::get('/admin/reporte/columns', [ReporteController::class, 'columnsReporte']);
        Route::post('/admin/reporte', [ReporteController::class, 'export']);
    }
);

Route::group(
    [
        'namespace'     => 'Solicitud',
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::post('/solicitud/get-count-convenios', [SolicitudController::class, 'getCountConvenios']);
        Route::get('/solicitud/plazo-avion', [SolicitudController::class, 'isPlazoAvion']);

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

        Route::get('/informe/update/{uuid}', [SolicitudController::class, 'getInformeCometidoUpdate']);
        Route::put('/informe/update/{uuid}', [SolicitudController::class, 'updateInformeCometido']);
        Route::post('/informe/store', [SolicitudController::class, 'storeInformeCometido']);
        Route::delete('/informe/delete/{uuid}', [SolicitudController::class, 'deleteInformeCometido']);
        Route::post('/informe/status', [SolicitudController::class, 'statusInformeCometido']);

        Route::get('/ausentismo/list', [AusentismosController::class, 'listAusentismos']);
        Route::post('/ausentismo/store', [AusentismosController::class, 'storeAusentismo']);
        Route::delete('/ausentismo/{uuid}', [AusentismosController::class, 'deleteAusentismo']);

        Route::get('/admin/lugares', [MantenedorAdminController::class, 'getLugares']);
        Route::post('/admin/lugares', [MantenedorAdminController::class, 'storeLugar']);
        Route::put('/admin/lugares/{id}', [MantenedorAdminController::class, 'changeStatusLugar']);

        Route::get('/admin/motivos', [MantenedorAdminController::class, 'getMotivos']);
        Route::post('/admin/motivos', [MantenedorAdminController::class, 'storeMotivo']);
        Route::put('/admin/motivos/{id}', [MantenedorAdminController::class, 'changeStatusMotivo']);

        Route::get('/documento/institucional', [DocumentoInstitucionalController::class, 'getDocumentosInstitucional']);
        Route::post('/documento/institucional', [DocumentoInstitucionalController::class, 'storeDocumento']);
        Route::delete('/documento/institucional/{uuid}', [DocumentoInstitucionalController::class, 'deleteDocumento']);
        Route::post('/documento/download-file/institucional/{uuid}', [DocumentoInstitucionalController::class, 'downloadFileInstitucional']);
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
        Route::get('/documento/{name}', [FileController::class, 'getDocument']);
    }
);

Route::group(
    [
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::post('/change-pass/{uuid}', [NewPasswordController::class, 'changePass']);
        Route::post('/change-data', [CuentaController::class, 'changeData']);
        Route::get('/links', [LinksController::class, 'getLinks']);
    }
);

Route::get('/share/lugares', function(){
    $lugares = Lugar::orderBy('nombre', 'ASC')->get();
    return response()->json($lugares);
});

Route::get('/share/lugares/{id}', function ($id) {
    $lugar = Lugar::find($id);
    return response()->json($lugar);
});

Route::get('/maintenance-mode', function () {
    return response()->json(['maintenance' => App::isDownForMaintenance()]);
});
