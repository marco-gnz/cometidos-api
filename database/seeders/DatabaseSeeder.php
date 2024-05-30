<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call(DatosMaestrosSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(TestEscalaValoresSeeder::class);
        $this->call(ConfigurationSeeder::class);
        $this->call(PermissionsSeeder::class);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
