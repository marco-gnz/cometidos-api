<?php

namespace Database\Seeders;

use App\Models\ActividadGasto;
use App\Models\Motivo;
use App\Models\Transporte;
use App\Models\Establecimiento;
use Illuminate\Database\Seeder;

class DatosMaestrosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ActividadGasto::truncate();
        $actividad = new ActividadGasto();
        $actividad->nombre = 'Avión';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Bus';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Particular';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Vehículo institucional';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Locomoción colectiva';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Taxi';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Metro';
        $actividad->save();

        Motivo::truncate();
        $motivo = new Motivo();
        $motivo->nombre = 'Motivo 1';
        $motivo->save();

        $motivo = new Motivo();
        $motivo->nombre = 'Motivo 2';
        $motivo->save();

        $motivo = new Motivo();
        $motivo->nombre = 'Motivo 3';
        $motivo->save();

        Transporte::truncate();
        $transporte = new Transporte();
        $transporte->nombre = 'Avión';
        $transporte->save();

        $transporte = new Transporte();
        $transporte->nombre = 'Bus';
        $transporte->save();

        $transporte = new Transporte();
        $transporte->nombre = 'Particular';
        $transporte->save();

        $transporte = new Transporte();
        $transporte->nombre = 'Vehículo institucional';
        $transporte->save();


        $transporte = new Transporte();
        $transporte->nombre = 'Locomoción colectiva';
        $transporte->save();

        $transporte = new Transporte();
        $transporte->nombre = 'Taxi';
        $transporte->save();

        Establecimiento::truncate(); //evita duplicar datos
        $establecimiento            = new Establecimiento();
        $establecimiento->cod_sirh  = '1025';
        $establecimiento->sigla     = 'DSSO';
        $establecimiento->nombre    = 'DIRECCIÓN DE SERVICIO DE SALUD OSORNO';
        $establecimiento->save();

        $establecimiento            = new Establecimiento();
        $establecimiento->cod_sirh  = '1041';
        $establecimiento->sigla     = 'HPO';
        $establecimiento->nombre    = 'HOSPITAL PUERTO OCTAY';
        $establecimiento->save();

        $establecimiento            = new Establecimiento();
        $establecimiento->cod_sirh  = '1040';
        $establecimiento->sigla     = 'HPU';
        $establecimiento->nombre    = 'HOSPITAL PURRANQUE';
        $establecimiento->save();

        $establecimiento            = new Establecimiento();
        $establecimiento->cod_sirh  = '1042';
        $establecimiento->sigla     = 'HRN';
        $establecimiento->nombre    = 'HOSPITAL RIO NEGRO';
        $establecimiento->save();

        $establecimiento            = new Establecimiento();
        $establecimiento->cod_sirh  = '1043';
        $establecimiento->sigla     = 'HFSLKMM';
        $establecimiento->nombre    = 'HOSPITAL FUTA SRUKA LAWENCHE';
        $establecimiento->save();

        $establecimiento            = new Establecimiento();
        $establecimiento->cod_sirh  = '1044';
        $establecimiento->sigla     = 'HPMULEN';
        $establecimiento->nombre    = 'HOSPITAL PU MULEN';
        $establecimiento->save();

        $establecimiento            = new Establecimiento();
        $establecimiento->cod_sirh  = '1027';
        $establecimiento->sigla     = 'HBSJO';
        $establecimiento->nombre    = 'HOSPITAL BASE SAN JOSÉ OSORNO';
        $establecimiento->save();
    }
}
