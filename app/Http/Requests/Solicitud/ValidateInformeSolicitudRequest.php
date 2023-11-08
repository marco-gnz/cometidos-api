<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;

class ValidateInformeSolicitudRequest extends FormRequest
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
            'derecho_pago'              => ['required'],
            'motivos_cometido'          => ['required', 'array'],
            'lugares_cometido'          => ['required', 'array'],
            'actividad_realizada'       => ['required'],
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
        ];
    }
}
