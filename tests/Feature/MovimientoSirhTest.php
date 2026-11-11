<?php

namespace Tests\Feature;

use App\Models\Solicitud;
use App\Models\TipoComision;
use App\Models\User;
use App\Services\Sirh\MovimientoClient;
use App\Services\Sirh\SolicitudMovimientoService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class MovimientoSirhTest extends TestCase
{
    public function test_tipo_comision_normaliza_codigos_y_solo_acepta_movimiento()
    {
        $tipo = new TipoComision(['cod_web_service' => '001;2;1']);
        $this->assertSame('1;2', $tipo->cod_web_service);
        $this->assertSame(2, $tipo->metodo_consulta);
        $this->expectException(\InvalidArgumentException::class);
        $tipo->metodo_consulta = 1;
    }

    public function test_migracion_asigna_codigos_sin_eliminar_tipos_existentes()
    {
        config(['database.connections.sirh_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        $previous = config('database.default');
        config(['database.default' => 'sirh_test']);
        try {
            \Illuminate\Support\Facades\Schema::create('tipo_comisions', function ($table) {
                $table->increments('id');
                $table->string('nombre');
            });
            $db = \Illuminate\Support\Facades\DB::table('tipo_comisions');
            foreach (['ESTUDIOS', 'CAPACITACIÓN', 'DE SERVICIO', 'CAPACITACIÓN FINANCIAMIENTO CENTRALIZADO', 'OTROS'] as $nombre) {
                $db->insert(['nombre' => $nombre]);
            }
            require_once database_path('migrations/2026_11_06_000001_add_consulta_sirh_to_tipo_comisions.php');
            (new \AddConsultaSirhToTipoComisions())->up();
            $this->assertSame(['2', '3', '1', '3', null], $db->orderBy('id')->pluck('cod_web_service')->all());
            $this->assertSame([2, 2, 2, 2, 2], $db->orderBy('id')->pluck('metodo_consulta')->map(function ($value) { return (int) $value; })->all());
        } finally {
            \Illuminate\Support\Facades\DB::purge('sirh_test');
            config(['database.default' => $previous]);
        }
    }

    private function solicitud()
    {
        $solicitud = new Solicitud();
        $solicitud->setRawAttributes(['fecha_inicio' => '2026-11-01', 'fecha_termino' => '2026-11-03']);
        $solicitud->setRelation('tipoComision', new TipoComision(['cod_web_service' => '1;2']));
        $user = new User();
        $user->setRawAttributes(['rut_completo' => '12.345.678-5']);
        $solicitud->setRelation('funcionario', $user);
        return $solicitud;
    }

    public function test_consulta_codigos_en_orden_y_evalua_fechas()
    {
        $client = $this->createMock(MovimientoClient::class);
        $calls = [];
        $client->expects($this->exactly(2))->method('consultar')->willReturnCallback(function ($filtros) use (&$calls) {
            $calls[] = $filtros;
            return $filtros['tipoPermiso'] === '1' ? [] : [['Fecha_de_inicio' => '01/11/2026', 'Fecha_de_termino' => '03/11/2026']];
        });
        $result = (new SolicitudMovimientoService($client))->consultar($this->solicitud());
        $this->assertSame('con_registros', $result['estado']);
        $this->assertTrue($result['resumen']['coincidencia_exacta']);
        $this->assertSame(['1', '2'], array_column($calls, 'tipoPermiso'));
        $this->assertSame(2, $calls[0]['metodo_consulta']);
        $this->assertSame('12345678-5', $calls[0]['RUT']);
    }

    public function test_datos_incompletos_no_consultan_el_servicio()
    {
        $client = $this->createMock(MovimientoClient::class);
        $client->expects($this->never())->method('consultar');
        $solicitud = $this->solicitud();
        $solicitud->tipoComision->cod_web_service = null;
        $this->assertSame('datos_incompletos', (new SolicitudMovimientoService($client))->consultar($solicitud)['estado']);
    }

    public function test_soap_movimiento_y_errores_no_exponen_transporte()
    {
        config(['sirh.server_ip' => 'localhost', 'sirh.endpoint' => 'http://localhost/test', 'sirh.usuario' => 'a&b', 'sirh.clave' => '<secret>']);
        $client = new MovimientoClient(new Client(['handler' => HandlerStack::create(new MockHandler([new Response(500, [], 'secret')]))]));
        $xml = $client->envelope(['metodo_consulta' => 2, 'RUT' => '12345678-5', 'fechaDesde' => '01/11/2026', 'fechaHasta' => '03/11/2026', 'tipoPermiso' => '1']);
        $this->assertStringContainsString('<Movimiento', $xml);
        $this->assertStringContainsString('a&amp;b', $xml);
        $this->assertSame([], $client->parse('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><MovimientoResponse xmlns="http://tempuri.org/"/></soap:Body></soap:Envelope>'));
        $this->expectExceptionMessage('No fue posible consultar el servicio SIRH');
        $client->consultar(['metodo_consulta' => 2]);
    }

    public function test_xml_con_entidades_es_rechazado()
    {
        $client = new MovimientoClient(new Client());
        $this->expectException(\RuntimeException::class);
        $client->parse('<!DOCTYPE x [<!ENTITY x SYSTEM "file:///etc/passwd">]><x>&x;</x>');
    }
}
