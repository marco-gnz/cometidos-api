<?php

namespace Database\Seeders;

use App\Models\Escala;
use App\Models\Grado;
use Illuminate\Database\Seeder;

class TestEscalaValoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->escala2022();
        $this->escala2023();
        $this->escala2024();
    }

    public function escala2022()
    {
        //2023
        $grados_1 = Grado::whereBetween('nombre', [1, 4])->get();
        foreach ($grados_1 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2022-12-01';
            $escala->fecha_termino      = '2023-11-30';
            $escala->valor_dia_40       = 31448;
            $escala->valor_dia_100      = 78621;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }
        $grados_2 = Grado::whereBetween('nombre', [5, 10])->get();
        foreach ($grados_2 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2022-12-01';
            $escala->fecha_termino      = '2023-11-30';
            $escala->valor_dia_40       = 28928;
            $escala->valor_dia_100      = 72319;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }
        $grados_3 = Grado::whereBetween('nombre', [11, 31])->get();
        foreach ($grados_3 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2022-12-01';
            $escala->fecha_termino      = '2023-11-30';
            $escala->valor_dia_40       = 23477;
            $escala->valor_dia_100      = 58692;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }

        $escala = new Escala();
        $escala->fecha_inicio       = '2022-12-01';
        $escala->fecha_termino      = '2023-11-30';
        $escala->valor_dia_40       = 23477;
        $escala->valor_dia_100      = 58692;
        $escala->ley_id             = 1;
        $escala->save();

        $escala = new Escala();
        $escala->fecha_inicio       = '2022-12-01';
        $escala->fecha_termino      = '2023-11-30';
        $escala->valor_dia_40       = 23477;
        $escala->valor_dia_100      = 58692;
        $escala->ley_id             = 4;
        $escala->save();
    }

    public function escala2023()
    {
        //2023
        $grados_1 = Grado::whereBetween('nombre', [1, 4])->get();
        foreach ($grados_1 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2023-12-01';
            $escala->fecha_termino      = '2024-05-31';
            $escala->valor_dia_40       = 32801;
            $escala->valor_dia_100      = 82002;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }
        $grados_2 = Grado::whereBetween('nombre', [5, 10])->get();
        foreach ($grados_2 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2023-12-01';
            $escala->fecha_termino      = '2024-05-31';
            $escala->valor_dia_40       = 30172;
            $escala->valor_dia_100      = 75429;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }
        $grados_3 = Grado::whereBetween('nombre', [11, 31])->get();
        foreach ($grados_3 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2023-12-01';
            $escala->fecha_termino      = '2024-05-31';
            $escala->valor_dia_40       = 24486;
            $escala->valor_dia_100      = 61216;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }

        $escala = new Escala();
        $escala->fecha_inicio       = '2023-12-01';
        $escala->fecha_termino      = '2024-05-31';
        $escala->valor_dia_40       = 24486;
        $escala->valor_dia_100      = 61216;
        $escala->ley_id             = 1;
        $escala->save();

        $escala = new Escala();
        $escala->fecha_inicio       = '2023-12-01';
        $escala->fecha_termino      = '2024-05-31';
        $escala->valor_dia_40       = 24486;
        $escala->valor_dia_100      = 61216;
        $escala->ley_id             = 4;
        $escala->save();
    }

    public function escala2024()
    {
        //2023
        $grados_1 = Grado::whereBetween('nombre', [1, 4])->get();
        foreach ($grados_1 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2024-06-01';
            $escala->fecha_termino      = '2025-05-31';
            $escala->valor_dia_40       = 32965;
            $escala->valor_dia_100      = 82412;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }
        $grados_2 = Grado::whereBetween('nombre', [5, 10])->get();
        foreach ($grados_2 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2024-06-01';
            $escala->fecha_termino      = '2025-05-31';
            $escala->valor_dia_40       = 30322;
            $escala->valor_dia_100      = 75806;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }
        $grados_3 = Grado::whereBetween('nombre', [11, 31])->get();
        foreach ($grados_3 as $grado) {
            $escala = new Escala();
            $escala->fecha_inicio       = '2024-06-01';
            $escala->fecha_termino      = '2025-05-31';
            $escala->valor_dia_40       = 24609;
            $escala->valor_dia_100      = 61522;
            $escala->ley_id             = 2;
            $escala->grado_id           = $grado->id;
            $escala->save();
        }

        $escala = new Escala();
        $escala->fecha_inicio       = '2024-06-01';
        $escala->fecha_termino      = '2025-05-31';
        $escala->valor_dia_40       = 24609;
        $escala->valor_dia_100      = 61522;
        $escala->ley_id             = 1;
        $escala->save();

        $escala = new Escala();
        $escala->fecha_inicio       = '2024-06-01';
        $escala->fecha_termino      = '2025-05-31';
        $escala->valor_dia_40       = 24609;
        $escala->valor_dia_100      = 61522;
        $escala->ley_id             = 4;
        $escala->save();
    }
}
