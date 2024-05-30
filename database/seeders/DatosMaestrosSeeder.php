<?php

namespace Database\Seeders;

use App\Models\ActividadGasto;
use App\Models\Calidad;
use App\Models\Motivo;
use App\Models\Transporte;
use App\Models\Establecimiento;
use App\Models\Estamento;
use App\Models\Grado;
use App\Models\Hora;
use App\Models\Ilustre;
use App\Models\ItemPresupuestario;
use App\Models\Ley;
use App\Models\Lugar;
use App\Models\TipoComision;
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
        $actividad->nombre = 'Micro Bus';
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
        $actividad->nombre = 'Metro tren';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Buses Interurbanos';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Carga Combustible';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Carga Tajeta BIP';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Peaje - Tag';
        $actividad->save();

        $actividad = new ActividadGasto();
        $actividad->nombre = 'Otros';
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

        Ley::truncate();
        $ley = new Ley();
        $ley->nombre = '15.076';
        $ley->save();

        $ley = new Ley();
        $ley->nombre = '18.834';
        $ley->save();

        $ley = new Ley();
        $ley->nombre = '18.835';
        $ley->save();

        $ley = new Ley();
        $ley->nombre = '19.664';
        $ley->save();

        Estamento::truncate();
        $estamento = new Estamento();
        $estamento->nombre = 'ADMINISTRATIVOS';
        $estamento->save();

        $estamento = new Estamento();
        $estamento->nombre = 'AUXILIARES';
        $estamento->save();

        $estamento = new Estamento();
        $estamento->nombre = 'MÉDICOS';
        $estamento->save();

        $estamento = new Estamento();
        $estamento->nombre = 'ODONTÓLOGOS';
        $estamento->save();

        $estamento = new Estamento();
        $estamento->nombre = 'PROFESIONALES';
        $estamento->save();

        $estamento = new Estamento();
        $estamento->nombre = 'QUÍMICOS';
        $estamento->save();

        $estamento = new Estamento();
        $estamento->nombre = 'TÉCNICOS';
        $estamento->save();

        $estamento = new Estamento();
        $estamento->nombre = 'DIRECTIVOS';
        $estamento->save();

        Grado::truncate();
        for ($i = 2; $i <= 24; $i++) {
            $grado = new Grado();
            $grado->nombre = $i;
            $grado->save();
        }
        $grado = new Grado();
        $grado->nombre = 0;
        $grado->save();

        $grado = new Grado();
        $grado->nombre = 'Sin grado';
        $grado->save();

        Hora::truncate();
        $hora = new Hora();
        $hora->hora     = 11;
        $hora->nombre   = '11';
        $hora->save();

        $hora = new Hora();
        $hora->hora     = 22;
        $hora->nombre   = '22';
        $hora->save();

        $hora = new Hora();
        $hora->hora     = 33;
        $hora->nombre   = '33';
        $hora->save();

        $hora = new Hora();
        $hora->hora     = 44;
        $hora->nombre   = '44';
        $hora->save();

        $hora = new Hora();
        $hora->hora     = 0;
        $hora->nombre   = 'Otros';
        $hora->save();

        Calidad::truncate();
        $calidad = new Calidad();
        $calidad->nombre   = 'CONTRATADOS';
        $calidad->save();

        $calidad = new Calidad();
        $calidad->nombre   = 'HONORARIOS';
        $calidad->save();

        $calidad = new Calidad();
        $calidad->nombre   = 'TITULARES';
        $calidad->save();

        Ilustre::truncate();
        $ilustre = new Ilustre();
        $ilustre->nombre   = 'I.M SAN JUAN DE LA COSTA';
        $ilustre->save();

        $ilustre = new Ilustre();
        $ilustre->nombre   = 'I.M OSORNO';
        $ilustre->save();

        $ilustre = new Ilustre();
        $ilustre->nombre   = 'I.M PURRANQUE';
        $ilustre->save();

        $ilustre = new Ilustre();
        $ilustre->nombre   = 'I.M PUYEHUE';
        $ilustre->save();

        ItemPresupuestario::truncate();
        $itempresupuestario = new ItemPresupuestario();
        $itempresupuestario->nombre   = '22.03.001';
        $itempresupuestario->save();

        $itempresupuestario = new ItemPresupuestario();
        $itempresupuestario->nombre   = '22.08.007';
        $itempresupuestario->save();

        $itempresupuestario = new ItemPresupuestario();
        $itempresupuestario->nombre   = '22.04.999';
        $itempresupuestario->save();

        Lugar::truncate();
        $lugar = new Lugar();
        $lugar->nombre   = 'OSORNO';
        $lugar->save();

        $lugar = new Lugar();
        $lugar->nombre   = 'OSORNO (COSAM RAHUE)';
        $lugar->save();

        $lugar = new Lugar();
        $lugar->nombre   = 'PUERTO OCTAY';
        $lugar->save();

        $lugar = new Lugar();
        $lugar->nombre   = 'PUERTO OCTAY (HOSPITAL PUERTO OCTAY)';
        $lugar->save();

        TipoComision::truncate();
        $tipocomision = new TipoComision();
        $tipocomision->nombre   = 'ESTUDIOS';
        $tipocomision->save();

        $tipocomision = new TipoComision();
        $tipocomision->nombre   = 'CAPACITACIÓN';
        $tipocomision->save();

        $tipocomision = new TipoComision();
        $tipocomision->nombre   = 'SERVICIO';
        $tipocomision->save();

        $tipocomision = new TipoComision();
        $tipocomision->nombre   = 'CAPACITACIÓN FINANCIAMIENTO CENTRALIZADO';
        $tipocomision->save();

        $tipocomision = new TipoComision();
        $tipocomision->nombre   = 'OTROS';
        $tipocomision->save();
    }
}
