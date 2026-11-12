<?php

namespace App\Services\Archivos;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ValidarArchivo
{
    public function validar($file, $campo = 'file')
    {
        $mensaje = 'El archivo no está correcto o está dañado. Vuelva a guardarlo o exportarlo e intente cargarlo nuevamente.';
        if (!$file instanceof UploadedFile || !$file->isValid() || !$file->getRealPath() ||
            !is_readable($file->getRealPath()) || $file->getSize() <= 0) {
            throw ValidationException::withMessages([$campo => $mensaje]);
        }
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());
        $valido = true;
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $info = @getimagesize($path);
            $valido = $info && $info[2] === ($extension === 'png' ? IMAGETYPE_PNG : IMAGETYPE_JPEG);
            // Limitar la decodificación para evitar agotar memoria con imágenes de dimensiones enormes.
            if ($valido && $info[0] * $info[1] > 16000000) {
                throw ValidationException::withMessages([$campo => 'La imagen es demasiado grande. Reduzca sus dimensiones (máximo 16 megapíxeles) e intente nuevamente.']);
            }
            if ($valido && !function_exists('imagecreatefrompng')) {
                throw ValidationException::withMessages([$campo => 'No fue posible validar la imagen en el servidor. Intente nuevamente más tarde.']);
            }
            if ($valido) {
                $imagen = $extension === 'png' ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
                $valido = $imagen !== false;
                if ($imagen !== false) imagedestroy($imagen);
            }
        } elseif ($extension === 'pdf') {
            $stream = fopen($path, 'rb');
            try {
                $inicio = fread($stream, 1024);
                fseek($stream, max(0, $file->getSize() - 4096));
                $fin = stream_get_contents($stream);
                $valido = preg_match('/%PDF-1\.[0-9]|%PDF-2\.0/', $inicio) && strpos($fin, '%%EOF') !== false;
            } finally { fclose($stream); }
        } elseif ($extension === 'docx') {
            $valido = $this->docx($path);
        } elseif ($extension === 'doc') {
            $stream = fopen($path, 'rb');
            try { $valido = fread($stream, 8) === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" && $file->getSize() >= 512; }
            finally { fclose($stream); }
        }
        if (!$valido) throw ValidationException::withMessages([$campo => $mensaje]);
    }

    public function lote($files, $campo = 'archivos')
    {
        if ($files === null) return;
        if (!is_array($files)) throw ValidationException::withMessages([$campo => 'Debe enviar una lista de archivos válidos.']);
        foreach ($files as $indice => $file) $this->validar($file, $campo . '.' . $indice);
    }

    private function docx($path)
    {
        if (!class_exists(\ZipArchive::class)) return false;
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CHECKCONS) !== true) return false;
        try {
            if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('word/document.xml') === false) return false;
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $total += $stat['size'];
                if ($total > 50 * 1024 * 1024 || $zip->numFiles > 2000) return false;
                if (substr($stat['name'], -1) === '/') continue;
                $contenido = $zip->getFromIndex($i);
                if ($contenido === false || strlen($contenido) !== $stat['size'] ||
                    sprintf('%u', crc32($contenido)) !== sprintf('%u', $stat['crc'])) return false;
            }
            return true;
        } finally { $zip->close(); }
    }
}
