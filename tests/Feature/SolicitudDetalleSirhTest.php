<?php

namespace Tests\Feature;

use App\Http\Resources\Solicitud\ListSolicitudCompleteAdminResource;
use App\Models\Solicitud;
use App\Models\TipoComision;
use App\Models\User;
use App\Services\Sirh\ConsultaPersistidaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Tests\TestCase;

class SolicitudDetalleSirhTest extends TestCase
{
    private function datos($permiso, $detalle)
    {
        $solicitud = new Solicitud();
        $solicitud->setRelation('tipoComision', new TipoComision(['cod_web_service' => '3']));
        $user = $this->createMock(User::class);
        $user->method('can')->with('solicitud.datos.load-sirh', $solicitud)->willReturn($permiso);
        $request = Request::create('/api/admin/solicitudes/uuid/datos');
        $request->setUserResolver(function () use ($user) { return $user; });
        $resource = new class($solicitud) extends ListSolicitudCompleteAdminResource {
            public function sirh($request) { return $this->datosSirh($request); }
        };
        if ($detalle) $resource->conConsultaSirh();
        return $resource->sirh($request);
    }

    public function test_detalle_incluye_movimiento_y_codigos()
    {
        $service = $this->createMock(ConsultaPersistidaService::class);
        $service->expects($this->once())->method('obtener')->willReturn(['estado' => 'sin_registros', 'registros' => []]);
        $this->app->instance(ConsultaPersistidaService::class, $service);
        $data = $this->datos(true, true);
        $this->assertSame('sin_registros', $data['movimiento_sirh']['estado']);
        $this->assertSame('3', $data['tipo_comision_sirh']['cod_web_service']);
        $this->assertSame(['3'], $data['tipo_comision_sirh']['codigos_web_service']);
        $this->assertSame('Movimiento', $data['tipo_comision_sirh']['metodo_consulta_nombre']);
        $this->assertTrue($data['puede_consultar_movimiento_sirh']);
    }

    public function test_sin_permiso_habilita_consulta_automatica_sin_forzar()
    {
        $service = $this->createMock(ConsultaPersistidaService::class);
        $service->expects($this->once())->method('obtener')->with($this->isInstanceOf(Solicitud::class), true, false, false)
            ->willReturn(['estado' => 'con_registros', 'registros' => [['Descripcion_permiso' => 'COMETIDO']]]);
        $this->app->instance(ConsultaPersistidaService::class, $service);
        $data = $this->datos(false, true);
        $this->assertSame('COMETIDO', $data['movimiento_sirh']['registros'][0]['Descripcion_permiso']);
        $this->assertFalse($data['puede_consultar_movimiento_sirh']);
    }

    public function test_recurso_reutilizado_no_consulta_sirh()
    {
        $service = $this->createMock(ConsultaPersistidaService::class);
        $service->expects($this->never())->method('obtener');
        $this->app->instance(ConsultaPersistidaService::class, $service);
        $this->assertInstanceOf(MissingValue::class, $this->datos(true, false)['movimiento_sirh']);
    }

    public function test_ruta_especifica_no_es_capturada_por_el_detalle()
    {
        $route = $this->app['router']->getRoutes()->match(Request::create('/api/admin/solicitudes/movimiento-sirh/abc'));
        $this->assertSame('App\\Http\\Controllers\\Admin\\Solicitudes\\MovimientoSirhController@show', $route->getActionName());
    }
}
