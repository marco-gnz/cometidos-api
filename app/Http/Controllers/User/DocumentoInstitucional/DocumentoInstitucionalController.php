<?php

namespace App\Http\Controllers\User\DocumentoInstitucional;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstitucionalDocumentoRequest;
use App\Http\Resources\Documento\DocumentoInstitucionalResource;
use App\Models\InstitucionalDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentoInstitucionalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getDocumentosInstitucional()
    {
        try {
           $documentos = InstitucionalDocumento::orderBy('nombre', 'ASC')->get();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => DocumentoInstitucionalResource::collection($documentos)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeDocumento(StoreInstitucionalDocumentoRequest $request)
    {
        try {
            $this->authorize('create', InstitucionalDocumento::class);
            if ($request->hasFile('file')) {
                $file               = $request->file;
                $fileName           = 'institucional-documentos/' . $file->getClientOriginalName();
                $path               = Storage::disk('public')->putFileAs('archivos', $file, $fileName);
                $data = [
                    'url'           => $path,
                    'nombre'        => $request->nombre,
                    'observacion'   => $request->observacion ? $request->observacion : null,
                    'nombre_file'   => $file->getClientOriginalName(),
                    'size'          => $file->getSize(),
                    'format'        => $file->getMimeType(),
                    'extension'     => $file->getClientOriginalExtension(),
                    'is_valid'      => $file->isValid()
                ];

                $documento = InstitucionalDocumento::create($data);

                if($documento){
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Documento ingresado con éxito",
                            'message'       => null,
                            'data'          => DocumentoInstitucionalResource::make($documento)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function downloadFileInstitucional($uuid)
    {
        try {
            $file = InstitucionalDocumento::where('uuid', $uuid)->firstOrFail();

            $path = Storage::disk('public')->path($file->url);

            return response()->download($path, $file->nombre_file . '.' . $file->extension);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function deleteDocumento($uuid)
    {
        try {
            $file = InstitucionalDocumento::where('uuid', $uuid)->firstOrFail();
            $this->authorize('delete', $file);

            $delete = $file->delete();

            if($delete){
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Documento eliminado con éxito",
                        'message'       => null
                    )
                );
            }

        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
