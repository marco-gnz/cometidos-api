<?php

namespace App\Exports;

use App\Models\EstadoInformeCometido;
use App\Models\InformeCometido;
use App\Models\Solicitud;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Traits\StatusSolicitudTrait;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SolicitudesExport implements FromCollection, WithHeadings, WithColumnFormatting
{
    use StatusSolicitudTrait;

    protected $solicitudes;
    protected $columns;
    protected $filter_all;

    public function __construct($solicitudes, $columns, $filter_all)
    {
        $this->solicitudes = $solicitudes;
        $this->columns = $columns;
        $this->filter_all = $filter_all;
    }

    public function collection()
    {
        return $this->solicitudes->map(function ($solicitud) {
            // Itera sobre los campos seleccionados para construir cada fila
            return collect($this->columns)->map(function ($column) use ($solicitud) {
                return $this->getFieldValue($solicitud, $column);
            });
        });
    }

    public function headings(): array
    {
        // Regresa los nombres amigables en los encabezados
        return array_map(function ($column) {
            return $this->mapFieldToFriendlyName($column);
        }, $this->columns);
    }

    public function columnFormats(): array
    {
        $fechas_nativa      = $this->returnFechasNativa();
        $fechas_adicional   = $this->returnFechasAdicional();
        $horas_nativa       = $this->returnHorasNativa();
        $horas_adicional    = $this->returnHorasAdicional();
        $formats = [];

        foreach ($this->columns as $index => $column) {
            if (in_array($column, $fechas_nativa) || in_array($column, $fechas_adicional)) {
                $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
                $formats[$columnLetter] = NumberFormat::FORMAT_DATE_DDMMYYYY;
            }

            if (in_array($column, $horas_nativa) || in_array($column, $horas_adicional)) {
                $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
                $formats[$columnLetter] = 'hh:mm';
            }
        }

        return $formats;
    }

    public function getFieldValue($solicitud, $column)
    {

        $jefatura_directa   = $solicitud->jefaturaDirecta();
        $informe_cometido   = null;
        $calculo            = null;

        if (in_array($column, $this->columnsInformeCometido())) {
            $informe_cometido   = $solicitud->informeCometido();
        }

        if (in_array($column, $this->columnsValorizacion())) {
            $calculo            = $solicitud->getLastCalculo();
        }

        $fechas_nativa = $this->returnFechasNativa();
        if (in_array($column, $fechas_nativa)) {
            return $solicitud->$column ? Date::stringToExcel($solicitud->$column) : null;
        }

        if ($column === 'hora_llegada') {
            return $solicitud->hora_llegada ? $this->timeToExcelFormat($solicitud->hora_llegada) : null;
        }

        if ($column === 'hora_salida') {
            return $solicitud->hora_salida ? $this->timeToExcelFormat($solicitud->hora_salida) : null;
        }

        if ($column === 'codigo_sirh') {
            return $solicitud->nResolucionSirh();
        }

        if ($column === 'jefatura_directa_rut' && $jefatura_directa) {
            return $jefatura_directa->funcionario->rut_completo;
        }
        if ($column === 'jefatura_directa_nombres' && $jefatura_directa) {
            return $jefatura_directa->funcionario->nombre_completo;
        }
        if ($column === 'jefatura_directa_email' && $jefatura_directa) {
            return $jefatura_directa->funcionario->email;
        }

        if ($column === 'status') {
            return Solicitud::STATUS_NOM[$solicitud->status] ?? 'Desconocido';
        }

        if ($column === 'derecho_pago') {
            return $solicitud->derecho_pago ? 'Si' : 'No';
        }

        if ($column === 'utiliza_transporte') {
            return $solicitud->utiliza_transporte ? 'Si' : 'No';
        }

        if ($column === 'viaja_acompaniante') {
            return $solicitud->viaja_acompaniante ? 'Si' : 'No';
        }

        if ($column === 'jornada') {
            return Solicitud::JORNADA_ABRE[$solicitud->jornada] ?? 'Desconocido';
        }

        if ($column === 'afecta_convenio') {
            return $solicitud->afecta_convenio ? 'Si' : 'No';
        }

        if ($column === 'alimentacion_red') {
            return $solicitud->alimentacion_red ? 'Si' : 'No';
        }

        if ($column === 'gastos_alimentacion') {
            return $solicitud->gastos_alimentacion ? 'Si' : 'No';
        }

        if ($column === 'gastos_alojamiento') {
            return $solicitud->gastos_alojamiento ? 'Si' : 'No';
        }

        if ($column === 'pernocta_lugar_residencia') {
            return $solicitud->pernocta_lugar_residencia ? 'Si' : 'No';
        }

        if ($column === 'load_sirh') {
            return $solicitud->isLoadSirhInfo()->message;
        }

        if ($column === 'firmas') {
            $estados = $solicitud->estados; // precargado
            return collect($estados)->map(function ($estado) {
                return "{$estado->perfil->name}_{$estado->created_at}";
            })->implode('; ');
        }

        if ($column === 'informe_cometido_codigo' && $informe_cometido) {
            return optional($informe_cometido)->codigo;
        }

        if ($column === 'informe_cometido_estado' && $informe_cometido) {
            return $informe_cometido ? InformeCometido::STATUS_INGRESO_NOM[$informe_cometido->status_ingreso] : '';
        }

        if ($column === 'informe_cometido_fecha_inicio' && $informe_cometido) {
            return $informe_cometido->fecha_inicio ? Date::stringToExcel($informe_cometido->fecha_inicio) : null;
        }

        if ($column === 'informe_cometido_fecha_termino' && $informe_cometido) {
            return $informe_cometido->fecha_termino ? Date::stringToExcel($informe_cometido->fecha_termino) : null;
        }

        if ($column === 'informe_cometido_hora_llegada' && $informe_cometido) {
            return $informe_cometido->hora_llegada ? $this->timeToExcelFormat($informe_cometido->hora_llegada) : null;
        }

        if ($column === 'informe_cometido_hora_salida' && $informe_cometido) {
            return $informe_cometido->hora_salida ? $this->timeToExcelFormat($informe_cometido->hora_salida) : null;
        }

        if ($column === 'informe_cometido_actividad_realizada' && $informe_cometido) {
            return optional($informe_cometido)->actividad_realizada;
        }

        if ($column === 'informe_cometido_utiliza_transporte' && $informe_cometido) {
            return $informe_cometido ? ($informe_cometido->utiliza_transporte ? 'Si' : 'No') : '';
        }

        if ($column === 'informe_cometido_transportes' && $informe_cometido) {
            return $informe_cometido ? ($informe_cometido->transportes ? $informe_cometido->transportes->pluck('nombre')->implode('; ') : '') : '';
        }

        if ($column === 'informe_cometido_estado_informe' && $informe_cometido) {
            return $informe_cometido ? EstadoInformeCometido::STATUS_NOM[$informe_cometido->last_status] : '';
        }

        if ($column === 'informe_cometido_created_at' && $informe_cometido) {
            return $informe_cometido->fecha_by_user ? Date::stringToExcel($informe_cometido->fecha_by_user) : null;
        }

        if ($column === 'valorizacion_fecha_inicio_escala' && $calculo) {
            return $calculo->fecha_inicio ? Date::stringToExcel($calculo->fecha_inicio) : null;
        }

        if ($column === 'valorizacion_fecha_termino_escala' && $calculo) {
            return $calculo->fecha_termino ? Date::stringToExcel($calculo->fecha_termino) : null;
        }

        if ($column === 'valorizacion_grado_escala' && $calculo) {
            return $calculo ? ($calculo->grado ? $calculo->grado->nombre : '') : '';
        }

        if ($column === 'valorizacion_ley_escala' && $calculo) {
            return $calculo ? ($calculo->ley ? $calculo->ley->nombre : '') : '';
        }

        if ($column === 'valorizacion_valor_dia_40_escala' && $calculo) {
            return optional($calculo)->valor_dia_40;
        }

        if ($column === 'valorizacion_valor_dia_100_escala' && $calculo) {
            return optional($calculo)->valor_dia_100;
        }

        if ($column === 'valorizacion_n_dias_40' && $calculo) {
            return optional($calculo)->n_dias_40;
        }

        if ($column === 'valorizacion_n_dias_100' && $calculo) {
            return optional($calculo)->n_dias_100;
        }

        if ($column === 'valorizacion_monto_total' && $calculo) {
            return optional($calculo)->monto_total;
        }

        if ($column === 'valorizacion_n_dias_ajustes_40' && $calculo) {
            return $calculo ? $calculo->valorizacionAjuste40()->total_dias : '';
        }

        if ($column === 'valorizacion_n_dias_ajustes_100' && $calculo) {
            return $calculo ? $calculo->valorizacionAjuste100()->total_dias : '';
        }

        if ($column === 'valorizacion_monto_ajustes_40' && $calculo) {
            return $calculo ? $calculo->valorizacionAjusteMonto40()->total_monto_value : '';
        }

        if ($column === 'valorizacion_monto_ajustes_100' && $calculo) {
            return $calculo ? $calculo->valorizacionAjusteMonto100()->total_monto_value : '';
        }

        if ($column === 'valorizacion_total' && $calculo) {
            return $calculo ? $calculo->valorizacionTotalAjusteMonto()->total_valorizacion_value : '';
        }

        if (strpos($column, '.') !== false) {
            // campo relacionado, como 'ley.nombre'
            $relation = explode('.', $column)[0];  // 'ley'
            $relatedField = explode('.', $column)[1];  // 'nombre'

            if ($solicitud->$relation instanceof \Illuminate\Support\Collection) {
                // Si es una relación Many-to-Many, pluck los valores
                return $solicitud->$relation->pluck($relatedField)->implode('; ');
            }

            return optional($solicitud->$relation)->$relatedField;
        }

        return $solicitud->$column;
    }

    public function mapFieldToFriendlyName($column)
    {
        $map = [
            'codigo'                                        => 'N° de resolución solicitud',
            'codigo_sirh'                                   => 'N° de resolución SIRH',
            'fecha_inicio'                                  => 'Fecha de inicio',
            'fecha_termino'                                 => 'Fecha de término',
            'hora_llegada'                                  => 'Hora de salida',
            'hora_salida'                                   => 'Hora  de llegada',
            'derecho_pago'                                  => 'Derecho a viático',
            'utiliza_transporte'                            => 'Utiliza transporte',
            'transportes.nombre'                            => 'Transportes utilizados',
            'viaja_acompaniante'                            => 'Viaja con acompañante',
            'jornada'                                       => 'Jornada',
            'afecta_convenio'                               => 'Afecta a convenio',
            'alimentacion_red'                              => 'Alimentación en la red',
            'gastos_alimentacion'                           => 'Gastos de alimentación',
            'gastos_alojamiento'                            => 'Gastos de alojamiento',
            'pernocta_lugar_residencia'                     => 'Pernocta fuera',
            'n_dias_40'                                     => 'N días al 40%',
            'n_dias_100'                                    => 'N días al 100%',
            'actividad_realizada'                           => 'Actividad realizada',
            'observacion'                                   => 'Observación',
            'observacion_gastos'                            => 'Observación en gastos',
            'total_dias_cometido'                           => 'Total días cometido',
            'funcionario.rut_completo'                      => 'Rut funcionario',
            'funcionario.nombre_completo'                   => 'Nombre funcionario',
            'funcionario.email'                             => 'Correo electrónico funcionario',
            'jefatura_directa_rut'                          => 'Rut Jefatura Directa',
            'jefatura_directa_nombres'                      => 'Nombre Jefatura Directa',
            'jefatura_directa_email'                        => 'Correo electrónico Jefatura Directa',
            'departamento.nombre'                           => 'Departamento',
            'subdepartamento.nombre'                        => 'Subdepartamento',
            'ley.nombre'                                    => 'Ley',
            'calidad.nombre'                                => 'Calidad',
            'grado.nombre'                                  => 'Grado',
            'estamento.nombre'                              => 'Estamento',
            'establecimiento.nombre'                        => 'Establecimiento',
            'tipoComision.nombre'                           => 'Tipo de comisión',
            'cargo.nombre'                                  => 'Cargo',
            'hora.nombre'                                   => 'Hora',
            'itemPresupuestario.nombre'                     => 'Ítem presupuestario',
            'status'                                        => 'Estado solicitud',
            'motivos.nombre'                                => 'Motivo de cometido',
            'lugares.nombre'                                => 'Lugar de cometido',
            'load_sirh'                                     => 'Estado carga SIRH',
            'fecha_by_user'                                 => 'Fecha de ingreso',
            'firmas'                                        => 'Firmas del cometido',
            'informe_cometido_codigo'                       => 'Código informe cometido',
            'informe_cometido_estado'                       => 'Estado de ingreso informe cometido',
            'informe_cometido_fecha_inicio'                 => 'Fecha inicio informe cometido',
            'informe_cometido_fecha_termino'                => 'Fecha término informe cometido',
            'informe_cometido_hora_llegada'                 => 'Hora salida informe cometido',
            'informe_cometido_hora_salida'                  => 'Hora llegada informe cometido',
            'informe_cometido_actividad_realizada'          => 'Actividad informe cometido',
            'informe_cometido_utiliza_transporte'           => 'Utiliza tramsporte informe cometido',
            'informe_cometido_transportes'                  => 'Transporte utilizado informe cometido',
            'informe_cometido_estado_informe'               => 'Estado informe cometido',
            'informe_cometido_created_at'                   => 'Fecha ingreso informe cometido',
            'valorizacion_fecha_inicio_escala'              => 'Fecha inicio vigencia escala',
            'valorizacion_fecha_termino_escala'             => 'Fecha término vigencia escala',
            'valorizacion_grado_escala'                     => 'Grado escala',
            'valorizacion_ley_escala'                       => 'Ley escala',
            'valorizacion_valor_dia_40_escala'              => 'Valor día 40% escala',
            'valorizacion_valor_dia_100_escala'             => 'Valor día 100% escala',
            'valorizacion_n_dias_40'                        => 'N° días al 40%',
            'valorizacion_n_dias_100'                       => 'N° días al 100%',
            'valorizacion_monto_total'                      => 'Total valorización calculado',
            'valorizacion_n_dias_ajustes_40'                => 'N° días ajustes al 40%',
            'valorizacion_n_dias_ajustes_100'               => 'N° días ajustes al 100%',
            'valorizacion_monto_ajustes_40'                 => 'Monto ajustes al 40%',
            'valorizacion_monto_ajustes_100'                => 'Monto ajustes al 100%',
            'valorizacion_monto_ajustes'                    => 'Total monto en ajustes',
            'valorizacion_total'                            => 'TOTAL VALORIZACION COMETIDO',
        ];

        return $map[$column] ?? $column;
    }

    private function returnFechasNativa()
    {
        return [
            'fecha_inicio',
            'fecha_termino',
            'fecha_by_user',
        ];
    }

    private function returnFechasAdicional()
    {
        return [
            'informe_cometido_fecha_inicio',
            'informe_cometido_fecha_termino',
            'valorizacion_fecha_inicio_escala',
            'valorizacion_fecha_termino_escala',
            'informe_cometido_created_at'
        ];
    }

    private function returnHorasNativa()
    {
        return [
            'hora_llegada',
            'hora_salida'
        ];
    }

    private function returnHorasAdicional()
    {
        return [
            'hora.nombre',
            'informe_cometido_hora_llegada',
            'informe_cometido_hora_salida'
        ];
    }

    private function timeToExcelFormat($time)
    {
        // Convierte el string de hora a formato de Excel
        $timeParts = explode(':', $time);
        $hours = isset($timeParts[0]) ? (int) $timeParts[0] : 0;
        $minutes = isset($timeParts[1]) ? (int) $timeParts[1] : 0;
        $seconds = isset($timeParts[2]) ? (int) $timeParts[2] : 0;

        // Calcula la fracción del día
        return ($hours / 24) + ($minutes / 1440) + ($seconds / 86400);
    }

    private function columnsInformeCometido()
    {
        return [
            'informe_cometido_codigo',
            'informe_cometido_estado',
            'informe_cometido_fecha_inicio',
            'informe_cometido_fecha_termino',
            'informe_cometido_hora_llegada',
            'informe_cometido_hora_salida',
            'informe_cometido_actividad_realizada',
            'informe_cometido_utiliza_transporte',
            'informe_cometido_transportes',
            'informe_cometido_estado_informe',
            'informe_cometido_created_at'
        ];
    }

    private function columnsValorizacion()
    {
        return [
            'valorizacion_fecha_inicio_escala',
            'valorizacion_fecha_termino_escala',
            'valorizacion_grado_escala',
            'valorizacion_ley_escala',
            'valorizacion_valor_dia_40_escala',
            'valorizacion_valor_dia_100_escala',
            'valorizacion_n_dias_40',
            'valorizacion_n_dias_100',
            'valorizacion_monto_total',
            'valorizacion_n_dias_ajustes_40',
            'valorizacion_n_dias_ajustes_100',
            'valorizacion_monto_ajustes_40',
            'valorizacion_monto_ajustes_100',
            'valorizacion_monto_ajustes',
            'valorizacion_total',
        ];
    }
}
