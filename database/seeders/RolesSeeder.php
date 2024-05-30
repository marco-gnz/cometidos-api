<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::truncate();
        $solicitante                = Role::create(['name' => 'SOLICITANTE']);
        $admin_ejecutivo            = Role::create(['name' => 'EJECUTIVO']);
        $admin_jefe_directo         = Role::create(['name' => 'JEFE DIRECTO']);
        $admin_jefe_personal        = Role::create(['name' => 'JEFE PERSONAL']);
        $admin_sub_director         = Role::create(['name' => 'SUB DIRECTOR']);
        $admin_revisor_finanzas     = Role::create(['name' => 'REVISOR FINANZAS']);
        $admin_jefe_finanzas        = Role::create(['name' => 'SUPERVISOR FINANZAS']);
        $super_admin                = Role::create(['name' => 'SUPER ADMINISTRADOR']);
        $admin_abas                 = Role::create(['name' => 'ADMIN ABASTECIMIENTO']);
        $admin_capa                 = Role::create(['name' => 'ADMIN CAPACITACION']);
        $admin_depto                = Role::create(['name' => 'JEFE DEPARTAMENTO']);
        $admin                      = Role::create(['name' => 'ADMINISTRADOR']);
        $visor                      = Role::create(['name' => 'VISOR']);
    }
}
