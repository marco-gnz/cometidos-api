<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /* Permission::truncate(); */

        $permissions = [
            //solicitud
            [
                'model'         => 'solicitud.firma',
                'permissions'   => ['validar', 'anular']
            ],
            [
                'model'         => 'solicitud.datos',
                'permissions'   => ['ver', 'sincronizar-grupo', 'ver-documentos', 'editar-solicitud']
            ],
            [
                'model'         => 'solicitud.firmantes',
                'permissions'   => ['ver', 'editar']
            ],
            [
                'model'         => 'solicitud.valorizacion',
                'permissions'   => ['ver', 'crear']
            ],
            [
                'model'         => 'solicitud.ajuste',
                'permissions'   => ['ver', 'crear', 'editar']
            ],
            [
                'model'         => 'solicitud.convenio',
                'permissions'   => ['ver', 'crear']
            ],
            [
                'model'         => 'solicitud.rendiciones',
                'permissions'   => ['ver']
            ],
            [
                'model'         => 'solicitud.archivos',
                'permissions'   => ['ver', 'descargar']
            ],
            [
                'model'         => 'solicitud.informes',
                'permissions'   => ['ver', 'validar']
            ],
            [
                'model'         => 'solicitud.historial',
                'permissions'   => ['ver']
            ],
            //rendición
            [
                'model'         => 'rendicion',
                'permissions'   => ['dias-pago', 'sincronizar-cuenta-bancaria']
            ],
            [
                'model'         => 'rendicion.firma',
                'permissions'   => ['validar', 'anular', 'rechazar']
            ],
            [
                'model'         => 'rendicion.actividad',
                'permissions'   => ['ver', 'validar', 'resetear']
            ],

            //
            [
                'model'         => 'grupofirma',
                'permissions'   => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'model'         => 'convenio',
                'permissions'   => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'model'         => 'ausentismo',
                'permissions'   => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'model'         => 'funcionario',
                'permissions'   => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'model'         => 'usuarioespecial',
                'permissions'   => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'model'         => 'configuracion',
                'permissions'   => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'model'         => 'perfil',
                'permissions'   => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'model'         => 'solicitudes',
                'permissions'   => ['ver', 'reasignar']
            ],
            [
                'model'         => 'rendiciones',
                'permissions'   => ['ver']
            ],
            [
                'model'         => 'reasignacion',
                'permissions'   => ['ver','crear', 'editar', 'eliminar']
            ],
        ];

        foreach ($permissions as $data) {
            $model              = $data['model'];
            $only_permissions   = $data['permissions'];

            foreach ($only_permissions as $name) {
                Permission::create([
                    'name'      => "$model.$name",
                    'only_name' => $name,
                    'model'     => $model
                ]);
            }
        }
    }
}
