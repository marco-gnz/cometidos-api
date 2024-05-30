<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesHasPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $super_admin = Role::where('name', 'SUPER ADMINISTRADOR')->first();

        $permissions = Permission::whereIn('model', ['grupofirma', 'convenio', 'ausentismo', 'funcionario', 'usuarioespecial', 'configuracion', 'perfil', 'solicitudes', 'rendiciones','reasignacion'])->pluck('id')->toArray();

        if($super_admin){
            $super_admin->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'ADMINISTRADOR')->first();

        $models_name = [
            'grupofirma',
            'convenio',
            'ausentismo',
            'funcionario',
            'usuarioespecial',
            'reasignacion'
        ];
        $permissions_1 = Permission::whereIn('model', $models_name)
        ->where('only_name', '!=','eliminar')
        ->pluck('id')->toArray();

        $permissions_name = [
            'configuracion.ver',
            'perfil.ver',
            'solicitudes.ver',
            'rendiciones.ver'
        ];
        $permissions_2 = Permission::whereIn('name', $permissions_name)->pluck('id')->toArray();

        $permissions_total = array_merge($permissions_1, $permissions_2);

        if ($admin) {
            $admin->givePermissionTo($permissions_total);
        }

        $visor = Role::where('name', 'VISOR')->first();

        $permissions_name = [
            'solicitudes.ver',
            'rendiciones.ver'
        ];

        $permissions = Permission::whereIn('name', $permissions_name)->pluck('id')->toArray();

        if ($visor) {
            $visor->givePermissionTo($permissions_name);
        }
    }
}
