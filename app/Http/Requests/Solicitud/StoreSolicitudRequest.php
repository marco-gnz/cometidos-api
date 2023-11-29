<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitudRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'fecha_inicio'              => ['required', 'date', 'before_or_equal:fecha_termino'],
            'fecha_termino'             => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'hora_llegada'              => ['required', 'after_or_equal:hora_salida'],
            'hora_salida'               => ['required', 'before_or_equal:hora_llegada'],
            'derecho_pago'              => ['required', 'boolean'],
            'motivos_cometido'          => ['required', 'array'],
            'lugares_cometido'          => ['required', 'array'],
            'actividad_realizada'       => ['required'],
            'medio_transporte'          => ['required', 'array'],
            'gastos_alimentacion'       => ['required', 'boolean'],
            'gastos_alojamiento'        => ['required', 'boolean'],
            'pernocta_lugar_residencia' => ['required', 'boolean'],
            'n_dias_40'                 => ['nullable'],
            'n_dias_100'                => ['nullable'],
            'observacion_gastos'        => ['nullable'],
            'archivos'                  => ['nullable'],
        ];
    }

    public function messages()
    {
        return [
            'fecha_inicio.required'                 => 'La :attribute es obligatoria',
            'fecha_inicio.date'                     => 'La :attribute debe ser una fecha válida',
            'fecha_inicio.before_or_equal'          => 'La :attribute debe ser anterior a fecha de término',

            'fecha_termino.required'                => 'La :attribute es obligatoria',
            'fecha_termino.date'                    => 'La :attribute debe ser una fecha válida',
            'fecha_termino.after_or_equal'          => 'La :attribute debe ser superior a fecha de inicio',

            'hora_salida.required'                  => 'La :attribute es obligatoria',
            'hora_salida.after_or_equal'            => 'La :attribute debe ser superior a :hora_llegada',

            'hora_llegada.required'                 => 'La :attribute es obligatoria',
            'hora_salida.before_or_equal'           => 'La :attribute debe ser superior a :hora_salida',

            'derecho_pago.required'                 => 'El :attribute es obligatorio',

            'motivos_cometido.required'             => 'El :attribute es obligatorio',

            'lugares_cometido.required'             => 'El :attribute es obligatorio',

            'actividad_realizada.required'          => 'La :attribute es obligatoria',


            'medio_transporte.required'             => 'El :attribute es obligatorio',

            'gastos_alimentacion.required'          => 'El :attribute es obligatorio',

            'gastos_alojamiento.required'           => 'El :attribute es obligatorio',

            'actividades.required'                  => 'El :attribute es obligatorio',

            'actividades.*.mount.required'          => 'El :attribute es obligatorio',

            'n_dias_40.required'                    => 'El :attribute es obligatorio',

            'n_dias_100.required'                   => 'El :attribute es obligatorio',

            'observacion_pasajes.required'          => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'fecha_inicio'          => 'fecha de inicio',
            'fecha_termino'         => 'fecha de término',
            'hora_salida'           => 'hora de salida',
            'hora_llegada'          => 'hora de llegada',
            'derecho_pago'          => 'derecho a pago',
            'motivos_cometido'      => 'motivo de cometido',
            'lugares_cometido'      => 'lugar de cometido',
            'actividad_realizada'   => 'actividad realizada',
            'medio_transporte'      => 'medio de transporte',
            'gastos_alimentacion'   => 'gastos de alimentación',
            'gastos_alojamiento'    => 'gastos de alojamiento',
            'actividades'           => 'actividades',
            'actividades.*.mount'   => 'monto',
            'actividades.*.rinde_gastos_servicio' => 'rinde gasto servicio',
            'n_dias_40'             => 'n° de días de alojamiento',
            'n_dias_100'            => 'n° de días diarios',
            'observacion_pasajes'   => 'observación de pasajes'
        ];
    }
}
