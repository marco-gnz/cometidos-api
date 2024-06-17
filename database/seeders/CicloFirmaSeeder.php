<?php

namespace Database\Seeders;

use App\Models\CicloFirma;
use App\Models\Establecimiento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CicloFirmaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $establecimientos    = Establecimiento::all();
            $roles              = Role::whereNotIn('name', ['SUPER ADMINISTRADOR'])->get();

            foreach ($establecimientos as $establecimiento) {
                foreach ($roles as $role) {
                    if ($role->name === 'SOLICITANTE') {
                        $cicloFirma = new CicloFirma();
                        $cicloFirma->establecimiento_id = $establecimiento->id;
                        $cicloFirma->role_id            = $role->id;
                        $cicloFirma->save();
                        if ($cicloFirma) {
                            $name_permissions   = [
                                "solicitud.firma.anular",
                                "solicitud.datos.editar-solicitud",
                            ];
                            $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                            $cicloFirma->permissions()->attach($permissions);
                        }
                    } else  if ($role->name === 'JEFE DIRECTO') {
                        $cicloFirma = new CicloFirma();
                        $cicloFirma->establecimiento_id = $establecimiento->id;
                        $cicloFirma->role_id            = $role->id;
                        $cicloFirma->save();
                        if ($cicloFirma) {
                            $name_permissions   = [
                                'solicitud.firma.validar',
                                'solicitud.firma.reasignar',
                                'solicitud.datos.ver',
                                'solicitud.datos.ver-documentos',
                                'solicitud.firmantes.ver',
                                'solicitud.valorizacion.ver',
                                'solicitud.ajuste.ver',
                                'solicitud.convenio.ver',
                                'solicitud.rendiciones.ver',
                                'solicitud.archivos.ver',
                                'solicitud.archivos.descargar',
                                'solicitud.informes.ver',
                                "solicitud.informes.validar",
                                'solicitud.historial.ver',
                                'rendicion.firma.validar'
                            ];
                            $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                            $cicloFirma->permissions()->attach($permissions);
                        }
                    } else if ($role->name === 'EJECUTIVO') {
                        $cicloFirma = new CicloFirma();
                        $cicloFirma->establecimiento_id = $establecimiento->id;
                        $cicloFirma->role_id = $role->id;
                        $cicloFirma->save();
                        if ($cicloFirma) {
                            $name_permissions   = [
                                "solicitud.firma.validar",
                                "solicitud.firma.anular",
                                "solicitud.firma.reasignar",
                                "solicitud.datos.ver",
                                "solicitud.datos.sincronizar-grupo",
                                "solicitud.datos.ver-documentos",
                                "solicitud.datos.editar-solicitud",
                                "solicitud.firmantes.ver",
                                "solicitud.firmantes.editar",
                                "solicitud.valorizacion.ver",
                                "solicitud.valorizacion.crear",
                                'solicitud.ajuste.ver',
                                'solicitud.ajuste.crear',
                                'solicitud.ajuste.editar',
                                'solicitud.convenio.ver',
                                'solicitud.convenio.crear',
                                "solicitud.rendiciones.ver",
                                "solicitud.archivos.ver",
                                "solicitud.archivos.descargar",
                                "solicitud.informes.ver",
                                "solicitud.historial.ver"
                            ];
                            $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                            $cicloFirma->permissions()->attach($permissions);
                        }
                    } else if ($role->name === 'JEFE PERSONAL') {
                        $cicloFirma = new CicloFirma();
                        $cicloFirma->establecimiento_id = $establecimiento->id;
                        $cicloFirma->role_id = $role->id;
                        $cicloFirma->save();
                        if ($cicloFirma) {
                            $name_permissions   = [
                                "solicitud.firma.validar",
                                "solicitud.firma.reasignar",
                                "solicitud.datos.ver",
                                "solicitud.datos.ver-documentos",
                                "solicitud.firmantes.ver",
                                "solicitud.valorizacion.ver",
                                'solicitud.ajuste.ver',
                                'solicitud.convenio.ver',
                                "solicitud.rendiciones.ver",
                                "solicitud.archivos.ver",
                                "solicitud.archivos.descargar",
                                "solicitud.informes.ver",
                                "solicitud.historial.ver"
                            ];
                            $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                            $cicloFirma->permissions()->attach($permissions);
                        }
                    } else if ($role->name === 'JEFE DEPARTAMENTO' || $role->name === 'SUB DIRECTOR' || $role->name === 'REVISOR FINANZAS' || $role->name === 'SUPERVISOR FINANZAS') {
                        $cicloFirma = new CicloFirma();
                        $cicloFirma->establecimiento_id = $establecimiento->id;
                        $cicloFirma->role_id = $role->id;
                        $cicloFirma->save();
                        if ($cicloFirma) {
                            if ($role->name === 'SUB DIRECTOR') {
                                $name_permissions   = [
                                    "solicitud.firma.validar",
                                    "solicitud.firma.reasignar",
                                    "solicitud.datos.ver",
                                    "solicitud.datos.ver-documentos",
                                    "solicitud.firmantes.ver",
                                    "solicitud.valorizacion.ver",
                                    'solicitud.ajuste.ver',
                                    'solicitud.convenio.ver',
                                    'solicitud.informes.ver',
                                    "solicitud.rendiciones.ver",
                                    "solicitud.archivos.ver",
                                    "solicitud.archivos.descargar",
                                    "solicitud.historial.ver"
                                ];
                                $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                                $cicloFirma->permissions()->attach($permissions);
                            }else if ($role->name === 'JEFE DEPARTAMENTO') {
                                $name_permissions   = [
                                    "solicitud.firma.validar",
                                    "solicitud.firma.reasignar",
                                    "solicitud.datos.ver",
                                    "solicitud.datos.ver-documentos",
                                    "solicitud.firmantes.ver",
                                    "solicitud.valorizacion.ver",
                                    'solicitud.ajuste.ver',
                                    'solicitud.convenio.ver',
                                    'solicitud.informes.ver',
                                    "solicitud.rendiciones.ver",
                                    "solicitud.archivos.ver",
                                    "solicitud.archivos.descargar",
                                    "solicitud.historial.ver"
                                ];
                                $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                                $cicloFirma->permissions()->attach($permissions);
                            } else if ($role->name === 'REVISOR FINANZAS') {
                                $name_permissions   = [
                                    "solicitud.firma.validar",
                                    "solicitud.firma.reasignar",
                                    "solicitud.datos.ver",
                                    "solicitud.datos.ver-documentos",
                                    "solicitud.firmantes.ver",
                                    "solicitud.valorizacion.ver",
                                    'solicitud.ajuste.ver',
                                    'solicitud.convenio.ver',
                                    "solicitud.rendiciones.ver",
                                    'solicitud.informes.ver',
                                    "solicitud.archivos.ver",
                                    "solicitud.archivos.descargar",
                                    "solicitud.historial.ver",
                                    "rendicion.sincronizar-cuenta-bancaria",
                                    'rendicion.firma.rechazar',
                                    'rendicion.firma.anular',
                                    'rendicion.actividad.ver',
                                    'rendicion.actividad.validar',
                                    'rendicion.actividad.resetear',
                                ];
                                $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                                $cicloFirma->permissions()->attach($permissions);
                            } else if ($role->name === 'SUPERVISOR FINANZAS') {
                                $name_permissions   = [
                                    "solicitud.firma.validar",
                                    "solicitud.firma.reasignar",
                                    "solicitud.datos.ver",
                                    "solicitud.datos.ver-documentos",
                                    "solicitud.firmantes.ver",
                                    "solicitud.valorizacion.ver",
                                    'solicitud.ajuste.ver',
                                    'solicitud.convenio.ver',
                                    "solicitud.rendiciones.ver",
                                    'solicitud.informes.ver',
                                    "solicitud.archivos.ver",
                                    "solicitud.archivos.descargar",
                                    "solicitud.historial.ver",
                                    'rendicion.dias-pago',
                                    "rendicion.sincronizar-cuenta-bancaria",
                                    'rendicion.firma.rechazar',
                                    'rendicion.firma.validar',
                                    'rendicion.firma.anular',
                                    'rendicion.actividad.ver',
                                    'rendicion.actividad.validar',
                                    'rendicion.actividad.resetear',
                                ];
                                $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                                $cicloFirma->permissions()->attach($permissions);
                            }
                        }
                    } else if ($role->name === 'CAPACITACION' || $role->name === 'ABASTECIMIENTO') {
                        $cicloFirma = new CicloFirma();
                        $cicloFirma->establecimiento_id = $establecimiento->id;
                        $cicloFirma->role_id = $role->id;
                        $cicloFirma->save();
                        if ($cicloFirma) {
                            $name_permissions   = [
                                "solicitud.firma.validar",
                                "solicitud.firma.reasignar",
                                "solicitud.datos.ver",
                                "solicitud.datos.ver-documentos",
                                "solicitud.firmantes.ver",
                                "solicitud.valorizacion.ver",
                                'solicitud.ajuste.ver',
                                'solicitud.convenio.ver',
                                "solicitud.rendiciones.ver",
                                "solicitud.archivos.ver",
                                "solicitud.archivos.descargar",
                                "solicitud.informes.ver",
                                "solicitud.historial.ver"
                            ];
                            $permissions        = Permission::whereIn('name', $name_permissions)->pluck('id')->toArray();
                            $cicloFirma->permissions()->attach($permissions);
                        }
                    }
                }
            }
        } catch (\Exception $error) {
            return Log::info($error->getMessage());
        }
    }
}
