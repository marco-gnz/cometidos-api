<?php

namespace App\Exports;

use App\Models\EstadoInformeCometido;
use App\Models\InformeCometido;
use App\Models\Solicitud;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Traits\StatusSolicitudTrait;
use Illuminate\Support\Facades\Log;

class SolicitudesExport implements FromCollection, WithHeadings
{
    use StatusSolicitudTrait;

    protected $solicitudes;
    protected $columns;

    public function __construct($solicitudes, $columns)
    {
        $this->solicitudes = $solicitudes;
        $this->columns = $columns;
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

    protected function getFieldValue($solicitud, $column)
    {
        if($column === 'codigo_sirh'){
            return $solicitud->nResolucionSirh();
        }

        if ($column === 'status') {
            return Solicitud::STATUS_NOM[$solicitud->status] ?? 'Desconocido';
        }

        if($column === 'derecho_pago'){
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
            $estados = $solicitud->estados()->orderBy('created_at', 'ASC')->get();

            $firmas = collect($estados)->map(function($estado){
                return "{$estado->perfil->name}_{$estado->created_at}";
            })->implode('; ');

            return $firmas;
        }

        if ($column === 'informe_cometido_codigo') {
            $informe = $solicitud->informeCometido();
            return optional($informe)->codigo;
        }

        if ($column === 'informe_cometido_estado') {
            $informe = $solicitud->informeCometido();
            return $informe ? InformeCometido::STATUS_INGRESO_NOM[$informe->status_ingreso] : '';
        }

        if ($column === 'informe_cometido_fecha_inicio') {
            $informe = $solicitud->informeCometido();
            return optional($informe)->fecha_inicio;
        }

        if ($column === 'informe_cometido_fecha_termino') {
            $informe = $solicitud->informeCometido();
            return optional($informe)->fecha_termino;
        }

        if ($column === 'informe_cometido_hora_llegada') {
            $informe = $solicitud->informeCometido();
            return optional($informe)->hora_llegada;
        }

        if ($column === 'informe_cometido_hora_salida') {
            $informe = $solicitud->informeCometido();
            return optional($informe)->hora_salida;
        }

        if ($column === 'informe_cometido_actividad_realizada') {
            $informe = $solicitud->informeCometido();
            return optional($informe)->actividad_realizada;
        }

        if ($column === 'informe_cometido_utiliza_transporte') {
            $informe = $solicitud->informeCometido();
            return $informe ? ($informe->utiliza_transporte ? 'Si' : 'No') : '';
        }

        if ($column === 'informe_cometido_transportes') {
            $informe = $solicitud->informeCometido();
            return $informe ? ($informe->transportes ? $informe->transportes->pluck('nombre')->implode('; ') : '') : '';
        }

        if ($column === 'informe_cometido_estado_informe') {
            $informe = $solicitud->informeCometido();
            return $informe ? EstadoInformeCometido::STATUS_NOM[$informe->last_status] : '';
        }

        if ($column === 'informe_cometido_created_at') {
            $informe = $solicitud->informeCometido();
            return optional($informe)->fecha_by_user;
        }

        if ($column === 'valorizacion_fecha_inicio_escala') {
            $calculo = $solicitud->getLastCalculo();
            return optional($calculo)->fecha_inicio;
        }

        if ($column === 'valorizacion_fecha_termino_escala') {
            $calculo = $solicitud->getLastCalculo();
            return optional($calculo)->fecha_termino;
        }

        if ($column === 'valorizacion_grado_escala') {
            $calculo = $solicitud->getLastCalculo();
            return $calculo ? ($calculo->grado ? $calculo->grado->nombre : '') : '';
        }

        if ($column === 'valorizacion_ley_escala') {
            $calculo = $solicitud->getLastCalculo();
            return $calculo ? ($calculo->ley ? $calculo->ley->nombre : '') : '';
        }

        if ($column === 'valorizacion_valor_dia_40_escala') {
            $calculo = $solicitud->getLastCalculo();
            return optional($calculo)->valor_dia_40;
        }

        if ($column === 'valorizacion_valor_dia_100_escala') {
            $calculo = $solicitud->getLastCalculo();
            return optional($calculo)->valor_dia_100;
        }

        if ($column === 'valorizacion_n_dias_40') {
            $calculo = $solicitud->getLastCalculo();
            return optional($calculo)->n_dias_40;
        }

        if ($column === 'valorizacion_n_dias_100') {
            $calculo = $solicitud->getLastCalculo();
            return optional($calculo)->n_dias_100;
        }

        if ($column === 'valorizacion_monto_total') {
            $calculo = $solicitud->getLastCalculo();
            return optional($calculo)->monto_total;
        }

        if ($column === 'valorizacion_n_dias_ajustes_40') {
            $calculo = $solicitud->getLastCalculo();
            return $calculo ? $calculo->valorizacionAjuste40()->total_dias : '';
        }

        if ($column === 'valorizacion_n_dias_ajustes_100') {
            $calculo = $solicitud->getLastCalculo();
            return $calculo ? $calculo->valorizacionAjuste100()->total_dias : '';
        }

        if ($column === 'valorizacion_monto_ajustes_40') {
            $calculo = $solicitud->getLastCalculo();
            return $calculo ? $calculo->valorizacionAjusteMonto40()->total_monto_value : '';
        }

        if ($column === 'valorizacion_monto_ajustes_100') {
            $calculo = $solicitud->getLastCalculo();
            return $calculo ? $calculo->valorizacionAjusteMonto100()->total_monto_value : '';
        }

        if ($column === 'valorizacion_total') {
            $calculo = $solicitud->getLastCalculo();
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

    protected function mapFieldToFriendlyName($column)
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
}
