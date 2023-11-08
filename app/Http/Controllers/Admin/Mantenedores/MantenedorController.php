<?php

namespace App\Http\Controllers\Admin\Mantenedores;

use App\Http\Controllers\Controller;
use App\Models\ActividadGasto;
use App\Models\Lugar;
use App\Models\Motivo;
use App\Models\Transporte;
use Illuminate\Http\Request;

class MantenedorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getMotivos()
    {
        try {
            $motivos = Motivo::orderBy('nombre', 'ASC')->get();

            return response()->json($motivos);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getLugares()
    {
        try {
            $lugares = Lugar::orderBy('nombre', 'ASC')->get();

            return response()->json($lugares);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getTransporte()
    {
        try {
            $transportes = Transporte::orderBy('nombre', 'ASC')->get();

            return response()->json($transportes);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getActividades()
    {
        try {
            $actividades = ActividadGasto::orderBy('nombre', 'ASC')->get();
            foreach ($actividades as $actividad) {
                $actividad->{'rinde_gasto'} = false;
                $actividad->{'mount'}       = "";
            }

            return response()->json($actividades);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
