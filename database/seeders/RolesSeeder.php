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
        $admin_ejecutivo            = Role::create(['name' => 'EJECUTIVO']);
        $admin_jefe_directo         = Role::create(['name' => 'JEFE DIRECTO']);
        $admin_jefe_personal        = Role::create(['name' => 'JEFE PERSONAL']);
        $admin_sub_director         = Role::create(['name' => 'SUB DIRECTOR']);
        $admin_jefe_finanzas        = Role::create(['name' => 'JEFE FINANZAS']);

    }
}
