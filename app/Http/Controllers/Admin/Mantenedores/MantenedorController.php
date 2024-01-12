<?php

namespace App\Http\Controllers\Admin\Mantenedores;

use App\Http\Controllers\Controller;
use App\Models\ActividadGasto;
use App\Models\Country;
use App\Models\Departamento;
use App\Models\Establecimiento;
use App\Models\Lugar;
use App\Models\Motivo;
use App\Models\Solicitud;
use App\Models\SubDepartamento;
use App\Models\TipoComision;
use App\Models\Transporte;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class MantenedorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getStatusRechazo()
    {
        try {
            $estados = Solicitud::RECHAZO_STATUS;

            return response()->json($estados);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
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
                $actividad->{'rinde_gasto'}             = 0;
                $actividad->{'mount'}                   = "";
                $actividad->{'rinde_gastos_servicio'}   = null;
            }

            return response()->json($actividades);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getEstablecimientos()
    {
        try {
            $establecimientos = Establecimiento::orderBy('nombre', 'ASC')->get();

            return response()->json($establecimientos);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getDepartamentos()
    {
        try {
            $departamentos = Departamento::orderBy('nombre', 'ASC')->get();

            return response()->json($departamentos);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getSubdepartamentos()
    {
        try {
            $subdepartamentos = SubDepartamento::orderBy('nombre', 'ASC')->get();

            return response()->json($subdepartamentos);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getFirmantes(Request $request)
    {
        try {
            $firmantes = User::general($request->input)->orderBy('nombres', 'ASC')->get();

            return response()->json($firmantes);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getRoles()
    {
        try {
            $roles = Role::orderBy('name', 'ASC')->get();

            return response()->json($roles);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getUser($id)
    {
        try {
            $user = User::find($id);
            if ($user) {
                $user->{'role_id'}          = null;
            }

            return response()->json($user);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getTipoComisiones()
    {
        try {
            $tipo_comisiones = TipoComision::orderBy('nombre', 'ASC')->get();

            return response()->json($tipo_comisiones);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getJornadasCometido()
    {
        try {
            $jornadas = Solicitud::JORNADA_COMETIDOS;

            return response()->json($jornadas);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getPaises()
    {
        try {
            $paises = Country::orderBy('nombre', 'ASC')->get();

            return response()->json($paises);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
