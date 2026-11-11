<?php
namespace Tests\Feature;

use App\Jobs\ConsultarMovimientoSirh;
use App\Models\Solicitud;
use App\Services\Sirh\ConsultaPersistidaService;
use App\Services\Sirh\SolicitudMovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsultaSirhPersistidaTest extends TestCase
{
    private $service;
    private $raw;
    private $solicitud;
    private $filtros = ['metodo_consulta' => 2, 'RUT' => '12345678-5', 'fechaDesde' => '01/11/2026', 'fechaHasta' => '03/11/2026', 'tipoPermiso' => '1'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sirh_test', 'database.connections.sirh_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        require_once database_path('migrations/2026_11_10_000001_create_sirh_consultas_table.php');
        (new \CreateSirhConsultasTable())->up();
        Schema::create('solicituds', function ($t) { $t->increments('id'); $t->boolean('load_sirh')->default(false); $t->string('codigo'); $t->string('uuid')->nullable(); $t->integer('user_id')->nullable(); $t->integer('tipo_comision_id')->nullable(); });
        Schema::create('users', function ($t) { $t->increments('id'); });
        Schema::create('tipo_comisions', function ($t) { $t->increments('id'); });
        DB::table('solicituds')->insert(['id' => 1, 'codigo' => 'TEST', 'uuid' => 'test-uuid']);
        $this->solicitud = Solicitud::find(1);
        $this->raw = $this->createMock(SolicitudMovimientoService::class);
        $this->raw->method('filtros')->willReturnCallback(function () { return $this->filtros; });
        $this->service = new ConsultaPersistidaService($this->raw);
        Queue::fake();
    }

    protected function tearDown(): void
    {
        DB::purge('sirh_test');
        parent::tearDown();
    }

    private function resultado($estado = 'con_registros')
    {
        return ['estado' => $estado, 'mensaje' => 'Consulta realizada', 'consultado_en' => now()->toIso8601String(), 'filtros' => $this->filtros, 'resumen' => null, 'registros' => [['Descripcion_permiso' => 'COMETIDO']]];
    }

    private function guardar($vigencia)
    {
        DB::table('sirh_consultas')->insert(['clave' => $this->service->clave($this->filtros), 'resultado' => json_encode($this->resultado()), 'vigente_hasta' => $vigencia]);
    }

    public function test_resultado_vigente_se_lee_desde_bd_sin_soap_ni_cola()
    {
        $this->guardar(time() + 1800);
        $this->raw->expects($this->never())->method('consultar');
        $result = $this->service->obtener($this->solicitud);
        $this->assertSame('COMETIDO', $result['registros'][0]['Descripcion_permiso']);
        $this->assertTrue($result['vigente']);
        Queue::assertNothingPushed();
    }

    public function test_solicitudes_repetidas_solo_encolan_una_consulta()
    {
        $this->raw->expects($this->never())->method('consultar');
        $this->assertTrue($this->service->obtener($this->solicitud)['pendiente']);
        $this->service->obtener($this->solicitud);
        Queue::assertPushed(ConsultarMovimientoSirh::class, 1);
    }

    public function test_solo_estado_no_crea_reservas_ni_jobs()
    {
        $this->service->obtener($this->solicitud, false);
        $this->assertSame(0, DB::table('sirh_consultas')->count());
        Queue::assertNothingPushed();
    }

    public function test_vencido_se_muestra_mientras_se_actualiza_y_forzar_respeta_bloqueo()
    {
        $this->guardar(time() - 1);
        $result = $this->service->obtener($this->solicitud);
        $this->assertFalse($result['vigente']);
        $this->assertTrue($result['pendiente']);
        $this->assertSame('COMETIDO', $result['registros'][0]['Descripcion_permiso']);
        $this->service->obtener($this->solicitud, true, false, true);
        Queue::assertPushed(ConsultarMovimientoSirh::class, 1);
    }

    public function test_worker_persiste_y_reutiliza_resultado_y_error_con_ttl_corto()
    {
        $this->raw->expects($this->once())->method('consultar')->willReturn($this->resultado('servicio_no_disponible'));
        $clave = $this->service->clave($this->filtros);
        $token = $this->service->adquirir($clave, 600);
        $this->service->ejecutar(1, $clave, $token);
        $row = DB::table('sirh_consultas')->where('clave', $clave)->first();
        $this->assertEquals(0, DB::table('solicituds')->where('id', 1)->value('load_sirh'));
        $this->assertNull($row->token);
        $this->assertEqualsWithDelta(time() + 300, $row->vigente_hasta, 2);
        $this->assertSame('servicio_no_disponible', $this->service->obtener($this->solicitud)['estado']);
        Queue::assertNothingPushed();
    }

    public function test_consulta_con_registros_marca_carga_sin_movimiento_manual()
    {
        // No existe solicitud_sirh_loads en esta BD: el proceso no debe utilizarla.
        $this->raw->expects($this->once())->method('consultar')->willReturn($this->resultado());
        $clave = $this->service->clave($this->filtros);
        $this->service->ejecutar(1, $clave, $this->service->adquirir($clave, 600));
        $this->assertEquals(1, DB::table('solicituds')->where('id', 1)->value('load_sirh'));
        $this->assertTrue($this->service->obtener($this->solicitud)['load_sirh']);
    }

    public function test_resultado_positivo_almacenado_marca_sin_repetir_soap()
    {
        $this->guardar(time() + 1800);
        $this->raw->expects($this->never())->method('consultar');
        $this->assertTrue($this->service->obtener($this->solicitud)['load_sirh']);
        Queue::assertNothingPushed();
    }

    public function test_sin_registros_no_desmarca_cargas_previas()
    {
        DB::table('solicituds')->where('id', 1)->update(['load_sirh' => true]);
        $result = $this->resultado('sin_registros');
        $result['registros'] = [];
        $this->raw->expects($this->once())->method('consultar')->willReturn($result);
        $clave = $this->service->clave($this->filtros);
        $this->service->ejecutar(1, $clave, $this->service->adquirir($clave, 600));
        $this->assertEquals(1, DB::table('solicituds')->where('id', 1)->value('load_sirh'));
    }

    public function test_endpoint_sin_permiso_permite_actualizacion_automatica_pero_no_forzada()
    {
        $this->guardar(time() + 1800);
        $this->app->instance(ConsultaPersistidaService::class, $this->service);
        $user = $this->getMockBuilder(\App\Models\User::class)->onlyMethods(['checkPermissionTo'])->getMock();
        $user->method('checkPermissionTo')->willReturn(false);
        $user->setRawAttributes(['id' => 99]);
        $this->actingAs($user, 'sanctum');
        \Illuminate\Support\Facades\Gate::before(function () { return false; });
        $this->getJson('/api/admin/solicitudes/movimiento-sirh/test-uuid?solo_estado=1')
            ->assertOk()->assertJsonPath('estado', 'con_registros');
        $this->getJson('/api/admin/solicitudes/movimiento-sirh/test-uuid?forzar=1')->assertForbidden();
        $this->getJson('/api/admin/solicitudes/movimiento-sirh/test-uuid')->assertOk();
        Queue::assertNothingPushed();
        DB::table('sirh_consultas')->update(['vigente_hasta' => time() - 1]);
        $this->getJson('/api/admin/solicitudes/movimiento-sirh/test-uuid')
            ->assertOk()->assertJsonPath('pendiente', true);
        Queue::assertPushed(ConsultarMovimientoSirh::class, 1);
        $this->getJson('/api/admin/solicitudes/movimiento-sirh/test-uuid')->assertOk();
        Queue::assertPushed(ConsultarMovimientoSirh::class, 1);
    }

    public function test_endpoint_sin_sesion_no_puede_leer_ni_programar_consultas()
    {
        $this->getJson('/api/admin/solicitudes/movimiento-sirh/test-uuid')->assertUnauthorized();
        Queue::assertNothingPushed();
    }

    public function test_listado_prepara_solo_pagina_y_reutiliza_cache_sin_soap()
    {
        $this->guardar(time() + 1800);
        DB::table('solicituds')->insert(['id' => 2, 'codigo' => 'FUERA-PAGINA', 'uuid' => 'otro']);
        $this->raw->expects($this->never())->method('consultar');
        $pagina = Solicitud::where('id', 1)->get();
        $this->service->actualizarListado($pagina);
        $this->assertSame('con_registros', $pagina->first()->getRelation('estadoSirh')['estado']);
        $this->assertTrue($pagina->first()->load_sirh);
        $this->assertEquals(0, DB::table('solicituds')->where('id', 2)->value('load_sirh'));
        Queue::assertNothingPushed();
    }

    public function test_listado_encola_faltantes_una_vez_y_devuelve_pendiente()
    {
        $this->raw->expects($this->never())->method('consultar');
        $pagina = Solicitud::where('id', 1)->get();
        $this->service->actualizarListado($pagina);
        $this->assertTrue($pagina->first()->getRelation('estadoSirh')['pendiente']);
        $this->service->actualizarListado($pagina);
        Queue::assertPushed(ConsultarMovimientoSirh::class, 1);
    }

    public function test_listado_vencido_actualiza_sin_borrar_el_resultado_anterior()
    {
        $this->guardar(time() - 1);
        $pagina = Solicitud::where('id', 1)->get();
        $this->service->actualizarListado($pagina);
        $estado = $pagina->first()->getRelation('estadoSirh');
        $this->assertSame('con_registros', $estado['estado']);
        $this->assertTrue($estado['pendiente']);
        $this->assertFalse($estado['vigente']);
        Queue::assertPushed(ConsultarMovimientoSirh::class, 1);
    }

    public function test_cambio_de_filtros_invalida_resultado_y_descarta_job_obsoleto()
    {
        $this->guardar(time() + 1800);
        $clave = $this->service->clave($this->filtros);
        $token = $this->service->adquirir($clave, 600);
        $this->filtros['tipoPermiso'] = '3';
        $this->raw->expects($this->never())->method('consultar');
        $this->assertTrue($this->service->ejecutar(1, $clave, $token));
        $this->assertSame('pendiente', $this->service->obtener($this->solicitud)['estado']);
    }
}
