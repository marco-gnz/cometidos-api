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
        $extension = $file->extension();
        $nombre = (string) Str::uuid() . ($extension ? '.' . $extension : '');
        $path = Storage::disk('public')->putFileAs('archivos/' . $carpeta, $file, $nombre);
        if (!$path) throw new RuntimeException('No fue posible almacenar el archivo.');

        try {
            $documento = $modelo::create(array_merge($datos, ['url' => $path]));
            if (!$documento->exists) throw new RuntimeException('No fue posible registrar el archivo.');
            $this->creados[] = [$modelo, $path];
            return $documento;
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($path);
            throw $error;
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
