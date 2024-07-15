<?php

namespace App\Http\Controllers\Admin\Mantenedores;

use App\Http\Controllers\Controller;
use App\Models\ActividadGasto;
use App\Models\Banco;
use App\Models\Calidad;
use App\Models\Cargo;
use App\Models\Concepto;
use App\Models\Country;
use App\Models\CuentaBancaria;
use App\Models\Departamento;
use App\Models\Establecimiento;
use App\Models\EstadoInformeCometido;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\Estamento;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Ilustre;
use App\Models\InformeCometido;
use App\Models\Ley;
use App\Models\Lugar;
use App\Models\Motivo;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use App\Models\SubDepartamento;
use App\Models\TipoComision;
use App\Models\Transporte;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MantenedorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getPermisosAdicionales(Request $request)
    {
        try {
            $permissions    = [];
            $roles          = Role::whereIn('id', $request->roles_id)->get();

            foreach ($roles as $role) {
                foreach ($role->permissions as $permission) {
                    if (!in_array($permission->id, $permissions)) {
                        array_push($permissions, $permission->id);
                    }
                }
            }
            $models = ['grupofirma', 'convenio', 'ausentismo', 'funcionario', 'usuarioespecial', 'configuracion', 'perfil', 'solicitudes', 'rendiciones', 'reasignacion'];
            $permissions_aditional = Permission::whereNotIn('id', $permissions)
                ->whereIn('model', $models)
                ->get();
            return response()->json($permissions_aditional);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getStatusRechazo()
    {
        try {
            $estados = EstadoSolicitud::RECHAZO_STATUS;
            return response()->json($estados);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getStatusCometido()
    {
        try {
            $estados = Solicitud::STATUS_COMETIDO;
            return response()->json($estados);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getStatusInforme()
    {
        try {
            $estados        = EstadoInformeCometido::STATUS_INFORME;
            $estadosIngreso = InformeCometido::STATUS_INGRESO_INFORME;
            return response()->json(array($estados, $estadosIngreso));
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getStatusRendicion()
    {
        try {
            $estados = EstadoProcesoRendicionGasto::STATUS_PROCESO;
            return response()->json($estados);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getMotivos()
    {
        try {
            $motivos = Motivo::where('active', true)->orderBy('nombre', 'ASC')->get();

            return response()->json($motivos);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getIlustres()
    {
        try {
            $ilustres = Ilustre::orderBy('nombre', 'ASC')->get();

            return response()->json($ilustres);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getLeys()
    {
        try {
            $leys = Ley::orderBy('nombre', 'ASC')->get();

            return response()->json($leys);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getGrados()
    {
        try {
            $grados = Grado::orderBy('nombre', 'DESC')->get();

            return response()->json($grados);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getLugares()
    {
        try {
            $lugares = Lugar::where('active', true)->orderBy('nombre', 'ASC')->get();

            return response()->json($lugares);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getTransporte()
    {
        try {
            $transportes = Transporte::orderBy('id', 'ASC')->get();

            return response()->json($transportes);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getDepartamentosToGroup($establecimiento_id)
    {
        $establecimiento = Establecimiento::find($establecimiento_id);

        if (!$establecimiento) {
            return response()->json('Establecimiento no encontrado', 404);
        }

        $departamentos = $establecimiento->grupos->map(function ($grupo) {
            return $grupo->departamento;
        })->unique();

        return response()->json($departamentos);
    }

    public function getSubdepartamentosToGroup($establecimiento_id, $departamento_id)
    {
        $grupos = Grupo::where('establecimiento_id', $establecimiento_id)
            ->where('departamento_id', $departamento_id)
            ->get();

        $subdepartamentos = $grupos->map(function ($grupo) {
            return $grupo->subdepartamento;
        })->unique();

        return response()->json($subdepartamentos);
    }

    public function getActividades($uuid)
    {
        try {
            $solicitud          = Solicitud::where('uuid', $uuid)->firstOrFail();
            $actividades        = ActividadGasto::orderBy('nombre', 'ASC')->get();
            $transportes_id     = $solicitud->transportes()->pluck('transporte_id')->toArray();
            foreach ($actividades as $actividad) {
                $actividad->{'rinde_gasto'}             = 0;
                $actividad->{'mount'}                   = null;
                $actividad->{'rinde_gastos_servicio'}   = null;
                if (in_array($actividad->id, $transportes_id)) {
                    $actividad->{'exist_solicitud'}   = true;
                } else {
                    $actividad->{'exist_solicitud'}   = false;
                }
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
            $firmante_uuid = $request->input('firmante_uuid');
            $is_habilitado = $request->input('ishabilitado');
            $is_subrogante = $request->input('is_subrogante');
            $is_admin      = $request->input('is_admin');
            $is_admin = $is_admin === 'true' ? true : false;
            $is_subrogante = $is_subrogante === 'true' ? true : false;
            $is_habilitado = $is_habilitado === 'true' ? true : false;

            $firmantes = User::general($request->input);

            if ($is_habilitado) {
                $firmantes = $firmantes->where('uuid', '!=', $firmante_uuid)
                    ->whereHas('firmas', function ($q) {
                        $q->where('status', true)
                            ->where('posicion_firma', '>', 0);
                    });
            }
            if ($is_subrogante) {
                $firmantes = $firmantes->where('is_subrogante', true);
            }

            if ($is_admin) {
                $firmantes = $firmantes->where('id', '!=', auth()->user()->id);
            }

            $firmantes = $firmantes->orderBy('nombres', 'ASC')
                ->get();

            return response()->json($firmantes);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getRoles()
    {
        try {
            $name_roles = ['SOLICITANTE', 'SUPER ADMINISTRADOR', 'ABASTECIMIENTO', 'CAPACITACION', 'VISOR', 'ADMINISTRADOR'];
            $roles = Role::whereNotIn('name', $name_roles)->orderBy('name', 'ASC')->get();

            return response()->json($roles);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getRolesPerfil()
    {
        try {
            $name_roles = ['SUPER ADMINISTRADOR', 'ADMINISTRADOR', 'VISOR'];
            $roles = Role::whereIn('name', $name_roles)->orderBy('name', 'ASC')->get();

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

    public function getConceptos()
    {
        try {
            $actividades = ActividadGasto::orderBy('nombre', 'ASC')->get();

            return response()->json($actividades);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getDatosBancarios()
    {
        try {
            $tipos_cuenta = CuentaBancaria::TYPES_ACCOUNT;
            $bancos = Banco::orderBy('nombre', 'ASC')->get();

            $data = (object) [
                'tipos_cuenta'  => $tipos_cuenta,
                'bancos'        => $bancos
            ];

            return response()->json($data);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getEstamentos()
    {
        try {
            $estamentos = Estamento::orderBy('nombre', 'ASC')->get();

            return response()->json($estamentos);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getCargos()
    {
        try {
            $cargos = Cargo::orderBy('nombre', 'ASC')->get();

            return response()->json($cargos);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getCalidad()
    {
        try {
            $calidads = Calidad::orderBy('nombre', 'ASC')->get();

            return response()->json($calidads);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getHoras()
    {
        try {
            $horas = Hora::orderBy('nombre', 'ASC')->get();

            return response()->json($horas);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
