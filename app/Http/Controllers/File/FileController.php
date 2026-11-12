<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Services\Archivos\ArchivoService;
use App\Http\Requests\Solicitud\ValidateFileSolicitudRequest;
use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use App\Models\Documento;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function downloadFile($uuid)
    {
        try {
            $file = Documento::where('uuid', $uuid)->firstOrFail();

            $path = Storage::disk('public')->path(ArchivoService::ruta($file->url));

            return response()->download($path, ArchivoService::nombreDescarga($file->nombre, $file->extension));
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function validateFileSolicitud(ValidateFileSolicitudRequest $request)
    {
        try {
            return true;
        } catch (\Exception $error) {
            return response()->json(['error' => 'Error al cargar archivo.'], 500);
        }
    }

    public function uploadFile(ValidateFileSolicitudRequest $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->uuid)->firstOrFail();

            if ($request->hasFile('file')) {
                $file               = $request->file;

                $data = [
                    'nombre'        => $file->getClientOriginalName(),
                    'size'          => $file->getSize(),
                    'format'        => $file->getMimeType(),
                    'extension'     => $file->getClientOriginalExtension(),
                    'is_valid'      => $file->isValid(),
                    'solicitud_id'  => $solicitud->id,
                    'user_id'       => $solicitud->user_id,
                    'model'         => Documento::MODEL_SOLICITUD
                ];

                $documento = app(ArchivoService::class)->guardar($file, app(ArchivoService::class)->carpetaSolicitud($solicitud), Documento::class, $data);

                if ($documento) {
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Archivo ingresado con éxito",
                            'message'       => null,
                            'data'          => ListSolicitudDocumentosResource::make($documento)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function deleteFile($uuid)
    {
        try {
            $file = Documento::where('uuid', $uuid)->firstOrFail();

            $delete = $file->delete();
            if ($delete) {
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Archivo eliminado con éxito",
                        'message'       => null,
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function getDocument($name)
    {
        try {
            $path = storage_path("app/public/institucional/{$name}");
            if (file_exists($path)) {
                $content  = file_get_contents($path);
                $mimeType = mime_content_type($path);

                return response($content, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', "inline; filename={$name}")
                    ->header('Content-Disposition', "inline; filename={$name}; filename*=UTF-8\'\'{$name}");
            } else {
                return false;
            }

        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
