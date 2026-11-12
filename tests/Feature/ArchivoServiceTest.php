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

    public function test_rechaza_archivos_vacios_imagenes_y_documentos_danados()
    {
        foreach ([['vacio.txt', ''], ['foto.png', 'esto no es una imagen'], ['doc.pdf', '%PDF-1.4 truncado'], ['carta.docx', 'zip roto'], ['carta.doc', 'no es un documento']] as [$nombre, $contenido]) {
            try {
                (new ArchivoService())->guardar(UploadedFile::fake()->createWithContent($nombre, $contenido), 'test', Documento::class, []);
                $this->fail('Debió rechazar ' . $nombre);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertNotEmpty($e->errors()['file']);
            }
        }
        $this->assertSame(0, Documento::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_imagen_y_docx_validos_superan_revision()
    {
        $validator = new \App\Services\Archivos\ValidarArchivo();
        $validator->validar(UploadedFile::fake()->image('foto.png', 10, 10));
        $path = tempnam(sys_get_temp_dir(), 'docx-test-');
        try {
            $zip = new \ZipArchive(); $zip->open($path, \ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types/>');
            $zip->addFromString('word/document.xml', '<?xml version="1.0"?><document/>'); $zip->close();
            $validator->validar(new UploadedFile($path, 'carta.docx', null, null, true));
            $this->assertSame(0, Documento::count());
        } finally { unlink($path); }
    }

    public function test_rechaza_carga_interrumpida()
    {
        $file = new UploadedFile(__FILE__, 'archivo.txt', 'text/plain', UPLOAD_ERR_PARTIAL, true);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new \App\Services\Archivos\ValidarArchivo())->validar($file);
    }

    public function test_archivo_que_desaparece_tras_insertar_no_deja_registro()
    {
        Documento::created(function ($doc) {
            if ($doc->nombre === 'desaparece') Storage::disk('public')->delete($doc->url);
        });
        try {
            (new ArchivoService())->guardar(UploadedFile::fake()->createWithContent('a.txt', 'contenido'), 'test', Documento::class, ['nombre' => 'desaparece']);
            $this->fail('Debió detectar el archivo faltante.');
        } catch (\App\Services\Archivos\ArchivoAlmacenamientoException $e) {
            $this->assertSame(0, Documento::count());
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_escritura_alterada_con_mismo_tamano_es_rechazada()
    {
        $disk = $this->createMock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->method('putFileAs')->willReturn('test/a.txt');
        $disk->method('exists')->willReturn(true);
        $disk->method('size')->willReturn(4);
        $stream = fopen('php://temp', 'r+'); fwrite($stream, 'otro'); rewind($stream);
        $disk->method('readStream')->willReturn($stream);
        $disk->expects($this->once())->method('delete')->with('test/a.txt');
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        try {
            (new ArchivoService())->guardar(UploadedFile::fake()->createWithContent('a.txt', 'dato'), 'test', Documento::class, []);
            $this->fail('Debió detectar el contenido alterado.');
        } catch (\App\Services\Archivos\ArchivoAlmacenamientoException $e) {
            $this->assertSame(0, Documento::count());
        }
    }

    public function test_cliente_recibe_error_422_para_archivo_danado()
    {
        $request = \App\Http\Requests\Solicitud\ValidateFileSolicitudRequest::create('/documento/upload-file', 'POST', [], [], ['file' => UploadedFile::fake()->createWithContent('foto.png', 'imagen dañada')]);
        $response = (new \App\Http\Controllers\File\FileController())->uploadFile($request);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertNotEmpty($response->getData(true)['errors']['file']);
        $request = \Illuminate\Http\Request::create('/rendicion', 'POST', [], [], ['archivos' => [UploadedFile::fake()->createWithContent('foto.png', 'imagen dañada')]]);
        $response = (new \App\Http\Controllers\Rendicion\RendicionController())->storeRendicion($request);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertNotEmpty($response->getData(true)['errors']['archivos.0']);
    }

    public function test_ruta_prevalidacion_revisa_contenido_y_devuelve_mensaje_en_espanol()
    {
        foreach ([['falso.pdf', 'texto plano'], ['truncado.pdf', "%PDF-1.4\n1 0 obj <<>> endobj"], ['vacio.pdf', '']] as [$nombre, $contenido]) {
            $response = $this->postJson('/api/documento/validate-file', ['file' => UploadedFile::fake()->createWithContent($nombre, $contenido)]);
            $response->assertStatus(422)->assertJsonValidationErrors('file');
            $this->assertSame($response->json('errors.file.0'), $response->json('message'));
            $this->assertNotSame('The given data was invalid.', $response->json('message'));
        }
        $this->postJson('/api/documento/validate-file', ['file' => UploadedFile::fake()->image('buena.png', 10, 10)])->assertOk();
        $this->assertSame(0, Documento::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
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
