<?php

namespace App\Services\Archivos;

use App\Models\Documento;
use App\Models\InstitucionalDocumento;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ArchivoService
{
    private $creados = [];
    public function carpetaSolicitud(Solicitud $solicitud, $rendicion = null)
    {
        $fecha = Carbon::parse($solicitud->fecha_inicio);
        $tipo = $rendicion ? 'rendicion_de_gastos' : 'actividades';
        $carpeta = $tipo . '/' . $solicitud->funcionario->rut . '/' . $fecha->format('Y/m') . '/' . $solicitud->uuid;
        return $rendicion ? $carpeta . '/' . $rendicion->uuid : $carpeta;
    }

    public function guardar(UploadedFile $file, $carpeta, $modelo, array $datos)
    {
        app(ValidarArchivo::class)->validar($file);
        $extension = $file->extension();
        $nombre = (string) Str::uuid() . ($extension ? '.' . $extension : '');
        try { $path = Storage::disk('public')->putFileAs('archivos/' . $carpeta, $file, $nombre); }
        catch (\Throwable $error) { throw new ArchivoAlmacenamientoException('No fue posible almacenar el archivo. Intente nuevamente.', 0, $error); }
        if (!$path) throw new ArchivoAlmacenamientoException('No fue posible almacenar el archivo. Intente nuevamente.');

        try {
            $this->verificarAlmacenado($file, $path);
            $documento = $modelo::create(array_merge($datos, ['url' => $path]));
            if (!$documento->exists) throw new RuntimeException('No fue posible registrar el archivo.');
            // Volver a comprobar antes de confirmar el registro; compensar si desapareció o se truncó.
            try { $this->verificarAlmacenado($file, $path); }
            catch (\Throwable $error) {
                $modelo::whereKey($documento->getKey())->delete();
                throw $error;
            }
            $this->creados[] = [$modelo, $path];
            return $documento;
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($path);
            throw $error;
        }
    }

    private function verificarAlmacenado(UploadedFile $file, $path)
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path) || $disk->size($path) !== $file->getSize()) {
                throw new ArchivoAlmacenamientoException('El archivo no se almacenó correctamente. Intente cargarlo nuevamente.');
            }
            $stream = $disk->readStream($path);
            if (!is_resource($stream)) throw new ArchivoAlmacenamientoException('No fue posible verificar el archivo almacenado. Intente nuevamente.');
            try {
                $hash = hash_init('sha256');
                hash_update_stream($hash, $stream);
                $almacenado = hash_final($hash);
            } finally { fclose($stream); }
            if (!hash_equals(hash_file('sha256', $file->getRealPath()), $almacenado)) {
                throw new ArchivoAlmacenamientoException('El archivo almacenado está incompleto o dañado. Intente cargarlo nuevamente.');
            }
        } catch (ArchivoAlmacenamientoException $error) {
            throw $error;
        } catch (\Throwable $error) {
            throw new ArchivoAlmacenamientoException('No fue posible verificar el archivo almacenado. Intente cargarlo nuevamente.', 0, $error);
        }
    }

    public function limpiarHuerfanos()
    {
        foreach ($this->creados as [$modelo, $path]) {
            if (!$modelo::where('url', $path)->exists()) Storage::disk('public')->delete($path);
        }
        $this->creados = [];
    }

    public static function ruta($url)
    {
        return strpos($url, 'storage/') === 0 ? substr($url, 8) : $url;
    }

    public static function nombreDescarga($nombre, $extension)
    {
        $extension = ltrim((string) $extension, '.');
        if (!$extension || strcasecmp(pathinfo($nombre, PATHINFO_EXTENSION), $extension) === 0) return $nombre;
        return $nombre . '.' . $extension;
    }

    public static function eliminarSinReferencias($url)
    {
        $eliminar = function () use ($url) {
            $ruta = self::ruta($url);
            foreach ([Documento::class, InstitucionalDocumento::class] as $modelo) {
                if ($modelo::whereIn('url', [$ruta, 'storage/' . $ruta])->exists()) return;
            }
            Storage::disk('public')->delete($ruta);
        };
        // Esperar la confirmación de la transacción para no borrar archivos si se revierte.
        if (DB::transactionLevel() > 0) DB::afterCommit($eliminar);
        else $eliminar();
    }
}
