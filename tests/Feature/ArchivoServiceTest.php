<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\InstitucionalDocumento;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\Archivos\ArchivoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchivoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'archivos_test', 'database.connections.archivos_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Storage::fake('public');
        foreach (['documentos', 'institucional_documentos'] as $tabla) {
            Schema::create($tabla, function ($t) {
                $t->increments('id'); $t->string('uuid')->nullable(); $t->string('url');
                $t->string('extension')->nullable(); $t->string('nombre')->nullable(); $t->string('nombre_file')->nullable();
                $t->integer('user_id')->nullable(); $t->integer('user_id_by')->nullable();
                $t->timestamps();
            });
        }
        $user = new User(); $user->setRawAttributes(['id' => 1]); $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        DB::purge('archivos_test');
        parent::tearDown();
    }

    public function test_mismo_nombre_no_sobrescribe_entre_solicitudes_ni_dentro_de_una()
    {
        $service = new ArchivoService(); $archivos = [];
        foreach (['solicitud-1', 'solicitud-2', 'solicitud-1'] as $i => $uuid) {
            $solicitud = new Solicitud(); $solicitud->setRawAttributes(['uuid' => $uuid, 'fecha_inicio' => '2026-11-11']);
            $user = new User(); $user->setRawAttributes(['rut' => '12345678']); $solicitud->setRelation('funcionario', $user);
            $archivos[] = $service->guardar(UploadedFile::fake()->createWithContent('Foto 1.txt', 'contenido-' . $i), $service->carpetaSolicitud($solicitud), Documento::class, ['nombre' => 'Foto 1.txt']);
        }
        $this->assertCount(3, array_unique(array_map(function ($d) { return $d->url; }, $archivos)));
        foreach ($archivos as $i => $documento) {
            $this->assertSame('contenido-' . $i, Storage::disk('public')->get($documento->url));
            $this->assertSame('Foto 1.txt', $documento->nombre);
        }
    }

    public function test_rendiciones_se_separan_por_solicitud_y_proceso()
    {
        $solicitud = new Solicitud(); $solicitud->setRawAttributes(['uuid' => 'solicitud-1', 'fecha_inicio' => '2026-11-11']);
        $user = new User(); $user->setRawAttributes(['rut' => '12345678']); $solicitud->setRelation('funcionario', $user);
        $rendicion = new \App\Models\ProcesoRendicionGasto(); $rendicion->setRawAttributes(['uuid' => 'rendicion-1']);
        $this->assertSame('rendicion_de_gastos/12345678/2026/11/solicitud-1/rendicion-1', (new ArchivoService())->carpetaSolicitud($solicitud, $rendicion));
    }

    public function test_error_de_storage_no_registra_documento()
    {
        $disk = $this->createMock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->expects($this->once())->method('putFileAs')->willReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        try {
            (new ArchivoService())->guardar(UploadedFile::fake()->createWithContent('a.txt', 'dato'), 'test', Documento::class, []);
            $this->fail('Se esperaba error de almacenamiento.');
        } catch (\RuntimeException $e) {
            $this->assertSame(0, Documento::count());
        }
    }

    public function test_institucionales_mismo_nombre_tienen_rutas_distintas()
    {
        $service = new ArchivoService(); $urls = [];
        foreach (['primero', 'segundo'] as $contenido) {
            $doc = $service->guardar(UploadedFile::fake()->createWithContent('Manual.txt', $contenido), 'institucional-documentos', InstitucionalDocumento::class, ['nombre_file' => 'Manual.txt']);
            $urls[] = $doc->url;
        }
        $this->assertNotSame($urls[0], $urls[1]);
        $this->assertSame('primero', Storage::disk('public')->get($urls[0]));
    }

    public function test_fallo_de_bd_limpia_archivo_recien_subido()
    {
        try {
            (new ArchivoService())->guardar(UploadedFile::fake()->createWithContent('a.txt', 'dato'), 'actividades/test', Documento::class, ['nombre' => str_repeat('x', 10), 'url' => null, 'solicitud_id' => 2]);
            $this->fail('La columna solicitud_id no existe en esta BD de prueba.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_eliminar_registro_antiguo_compartido_preserva_archivo_hasta_ultima_referencia()
    {
        $ruta = 'archivos/actividades/antiguo.pdf'; Storage::disk('public')->put($ruta, 'original');
        $a = Documento::create(['url' => $ruta]);
        $b = Documento::create(['url' => 'storage/' . $ruta]);
        $c = InstitucionalDocumento::create(['url' => $ruta]);
        $a->delete(); $b->delete();
        Storage::disk('public')->assertExists($ruta);
        $c->delete(); Storage::disk('public')->assertMissing($ruta);
    }

    public function test_rollback_de_eliminacion_no_borra_archivo()
    {
        $ruta = 'archivos/antiguo.txt'; Storage::disk('public')->put($ruta, 'antiguo');
        $doc = Documento::create(['url' => $ruta]);
        DB::beginTransaction(); $doc->delete();
        Storage::disk('public')->assertExists($ruta);
        DB::rollBack();
        Storage::disk('public')->assertExists($ruta);
        $this->assertTrue(Documento::whereKey($doc->id)->exists());
        DB::beginTransaction(); Documento::find($doc->id)->delete(); DB::commit();
        Storage::disk('public')->assertMissing($ruta);
    }

    public function test_rollback_de_alta_limpia_archivos_nuevos()
    {
        $service = new ArchivoService();
        DB::beginTransaction();
        $doc = $service->guardar(UploadedFile::fake()->createWithContent('a.txt', 'nuevo'), 'actividades/test', Documento::class, ['nombre' => 'a.txt']);
        DB::rollBack();
        $service->limpiarHuerfanos();
        Storage::disk('public')->assertMissing($doc->url);
    }

    public function test_descarga_antigua_usa_ruta_guardada_y_nombre_sin_duplicar_extension()
    {
        $ruta = 'archivos/actividades/123/2024/01/Foto 1.jpg'; Storage::disk('public')->put($ruta, 'antigua');
        $doc = Documento::create(['url' => $ruta, 'nombre' => 'Foto 1.jpg', 'extension' => 'jpg']);
        $this->assertSame('antigua', Storage::disk('public')->get(ArchivoService::ruta($doc->url)));
        $this->assertSame('Foto 1.jpg', ArchivoService::nombreDescarga($doc->nombre, 'jpg'));
        $response = (new \App\Http\Controllers\File\FileController())->downloadFile($doc->uuid);
        $this->assertSame('antigua', file_get_contents($response->getFile()->getPathname()));
        $this->assertStringContainsString('Foto 1.jpg', $response->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('jpg.jpg', $response->headers->get('Content-Disposition'));
        $this->assertSame('Foto 1.jpg', ArchivoService::nombreDescarga('Foto 1', 'jpg'));
    }
}
