<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::truncate(); //evita duplicar datos
        $user = new User();
        $user->rut = 19270290;
        $user->dv = '9';
        $user->rut_completo = '19270290-9';
        $user->nombres = 'MARCO IGNACIO';
        $user->apellidos = 'GONZALEZ AZOCAR';
        $user->nombre_completo = 'MARCO IGNACIO GONZALEZ AZOCAR';
        $user->email = 'marcoi.gonzalez@redsalud.gob.cl';
        $user->password = bcrypt($user->rut);
        $user->ley_id   = 2;
        $user->grado_id = 1;
        $user->cargo_id = 37;
        $user->departamento_id = 79;
        $user->sub_departamento_id = 17;
        $user->establecimiento_id = 1;
        $user->hora_id = 4;
        $user->telefono = '912345678';
        $user->save();

        $user->createToken('sa');

        /* User::truncate(); //evita duplicar datos */
        $user2 = new User();
        $user2->rut = 14096576;
        $user2->dv = '6';
        $user2->rut_completo = '14096576-6';
        $user2->nombres = 'RENE ARIEL';
        $user2->apellidos = 'JARAMILLO MULLER';
        $user2->nombre_completo = 'RENE ARIEL JARAMILLO MULLER';
        $user2->email = 'rene.jaramillo@redsalud.gov.cl';
        $user2->password = bcrypt($user2->rut);
        $user2->ley_id   = 2;
        $user2->grado_id = 1;
        $user2->cargo_id = 142;
        $user2->departamento_id = 79;
        $user2->sub_departamento_id = 5;
        $user2->establecimiento_id = 1;
        $user2->hora_id = 4;
        $user2->telefono = '912345678';
        $user2->save();

        $user2->createToken('sa');
    }
}
