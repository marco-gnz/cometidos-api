<?php

namespace App\Http\Controllers\Admin\Solicitudes;

use App\Http\Controllers\Controller;
use App\Models\Solicitud;
use Illuminate\Http\Request;

class MovimientoSirhController extends Controller
{
    public function show(Request $request, $uuid)
    {
        $solicitud = Solicitud::with(['funcionario', 'tipoComision'])->where('uuid', $uuid)->firstOrFail();
        $request->validate(['forzar' => 'sometimes|boolean', 'solo_estado' => 'sometimes|boolean']);
        if ($request->boolean('forzar') && $request->boolean('solo_estado')) {
            return response()->json(['message' => 'Una lectura de estado no puede forzar una consulta.'], 422);
        }
        if ($request->boolean('forzar')) {
            $this->authorize('solicitud.datos.load-sirh', $solicitud);
        }
        $resultado = $solicitud->consultarMovimientoSirh($request->boolean('forzar'), $request->boolean('solo_estado'));
        $status = !empty($resultado['pendiente']) ? 200 : ($resultado['estado'] === 'datos_incompletos' ? 422
            : ($resultado['estado'] === 'servicio_no_disponible' ? 503 : 200));

        return response()->json($resultado, $status)->header('Cache-Control', 'no-store, private');
    }
}
