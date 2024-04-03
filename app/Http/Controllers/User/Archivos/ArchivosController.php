<?php

namespace App\Http\Controllers\User\Archivos;

use App\Http\Controllers\Controller;
use App\Http\Resources\Documento\ListDocumentoResource;
use App\Models\Documento;
use Illuminate\Http\Request;

class ArchivosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listArchivos()
    {
        $documentos = Documento::where('user_id', auth()->user()->id)->paginate(10);

        return response()->json(
            array(
                'status'        => 'success',
                'title'         => null,
                'message'       => null,

                'pagination' => [
                    'total'         => $documentos->total(),
                    'current_page'  => $documentos->currentPage(),
                    'per_page'      => $documentos->perPage(),
                    'last_page'     => $documentos->lastPage(),
                    'from'          => $documentos->firstItem(),
                    'to'            => $documentos->lastPage()
                ],
                'data'          => ListDocumentoResource::collection($documentos),
            )
        );
    }
}
