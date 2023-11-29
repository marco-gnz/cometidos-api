<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\ValidateFileSolicitudRequest;
use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use App\Models\Documento;
use App\Models\Solicitud;
use Carbon\Carbon;
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

            $path = Storage::disk('public')->path($file->url);

            return response()->download($path, $file->nombre . '.' . $file->extension);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function validateFileSolicitud(ValidateFileSolicitudRequest $request)
    {
        try {
            return true;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function uploadFile(ValidateFileSolicitudRequest $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->uuid)->firstOrFail();

            if ($request->hasFile('file')) {
                $file               = $request->file;
                $fecha_solicitud    = Carbon::parse($solicitud->fecha_inicio);
                $year               = $fecha_solicitud->format('Y');
                $month              = $fecha_solicitud->format('m');
                $fileName           = 'actividades/' . $solicitud->funcionario->rut . '/' . $year . '/' . $month . '/' . $file->getClientOriginalName();
                $path               = Storage::disk('public')->putFileAs('archivos', $file, $fileName);
                $data = [
                    'url'           => $path,
                    'nombre'        => $file->getClientOriginalName(),
                    'size'          => $file->getSize(),
                    'format'        => $file->getMimeType(),
                    'extension'     => $file->getClientOriginalExtension(),
                    'is_valid'      => $file->isValid(),
                    'solicitud_id'  => $solicitud->id,
                    'user_id'       => $solicitud->user_id
                ];

                $documento = Documento::create($data);

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
}
