<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSolicitudRequest extends FormRequest
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
            'solicitud_uuid'            => ['exists:solicituds,uuid'],
            'fecha_inicio'              => ['required', 'date', 'before_or_equal:fecha_termino'],
            'fecha_termino'             => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'hora_llegada'              => ['required'],
            'hora_salida'               => ['required'],
            'utiliza_transporte'        => ['required'],
            'viaja_acompaniante'        => ['required'],
            'alimentacion_red'          => ['required'],
            'derecho_pago'              => ['required', 'boolean'],
            'motivos_cometido'          => ['required', 'array'],
            'tipo_comision_id'          => ['required'],
            'jornada'                   => ['required'],
            'dentro_pais'               => ['required'],
            'lugares_cometido'          => ['required_if:dentro_pais,0', 'array'],
            'paises_cometido'           => ['required_if:dentro_pais,1', 'array'],
            'actividad_realizada'       => ['required'],
            'medio_transporte'          => ['required_if:utiliza_transporte,1', 'array'],
            'gastos_alimentacion'       => ['required', 'boolean'],
            'gastos_alojamiento'        => ['required', 'boolean'],
            'pernocta_lugar_residencia' => ['required', 'boolean'],
            'n_dias_40'                 => ['required', 'integer'],
            'n_dias_100'                => ['required', 'integer'],
            'observacion_gastos'        => ['nullable'],
            'archivos'                                  => [
                function ($attribute, $value, $fail) {
                    if (empty(request()->input('documentos')) && empty($value) && request()->input('derecho_pago') === 1) {
                        $fail('Debe cargar archivos al ser un cometido con derecho a pago');
                    }
                }
            ],
            'documentos'                => ['nullable'],
            'observacion'               => ['nullable'],
            'n_contacto'                => ['nullable', 'regex:/^\+?[0-9]{1,4}\s?[0-9]{6,}$/'],
            'email'                     => 'nullable|email'
        ];
    }

    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->derecho_pago) {
                if ((int) $this->n_dias_40 <= 0 && (int) $this->n_dias_100 <= 0) {
                    $validator->errors()->add('n_dias_40', 'Al menos uno de los días debe ser mayor que 0 cuando es con derecho a pago.');
                    $validator->errors()->add('n_dias_100', 'Al menos uno de los días debe ser mayor que 0 cuando es con derecho a pago.');
                }
            }

            if (is_array($this->medio_transporte) && in_array(1, $this->medio_transporte)) {
                $contactoVacio  = empty($this->n_contacto);
                $emailVacio     = empty($this->email);

                if ($contactoVacio && $emailVacio) {
                    $validator->errors()->add('n_contacto', 'El N° de contacto es obligatorio.');
                    $validator->errors()->add('email', 'El correo personal es obligatorio.');
                } elseif ($contactoVacio) {
                    $validator->errors()->add('n_contacto', 'El N° de contacto es obligatorio.');
                } elseif ($emailVacio) {
                    $validator->errors()->add('email', 'El correo personal es obligatorio.');
                }
            }
        });
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

            'hora_llegada.required'                 => 'La :attribute es obligatoria',

            'derecho_pago.required'                 => 'El :attribute es obligatorio',

            'motivos_cometido.required'             => 'El :attribute es obligatorio',

            'alimentacion_red.required'             => 'La :attribute es obligatoria',

            'tipo_comision_id.required'             => 'El :attribute es obligatorio',

            'jornada.required'                      => 'La :attribute es obligatoria',

            'dentro_pais.required'                  => 'El :attribute es obligatorio',

            'lugares_cometido.required_if'          => 'El :attribute es obligatorio',

            'paises_cometido.required_if'           => 'El :attribute es obligatorio',

            'actividad_realizada.required'          => 'La :attribute es obligatoria',

            'medio_transporte.required_if'          => 'El :attribute es obligatorio',

            'gastos_alimentacion.required'          => 'El :attribute es obligatorio',

            'gastos_alojamiento.required'           => 'El :attribute es obligatorio',

            'actividades.required'                  => 'El :attribute es obligatorio',

            'actividades.*.mount.required'          => 'El :attribute es obligatorio',

            'n_dias_40.required'                    => 'El :attribute es obligatorio o dejarlo en 0',

            'n_dias_100.required'                   => 'El :attribute es obligatorio o dejarlo en 0',

            'observacion_pasajes.required'          => 'El :attribute es obligatorio',

            'email.email'                           => 'El :attribute debe ser un correo válido. Ej: Debe tener un @ y un .com',
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
            'tipo_comision_id'      => 'tipo de comisión',
            'jornada'               => 'jornada de cometido',
            'dentro_pais'           => 'destino',
            'alimentacion_red'      => 'alimentación en red',
            'lugares_cometido'      => 'lugar de cometido',
            'paises_cometido'       => 'país de cometido',
            'actividad_realizada'   => 'actividad realizada',
            'medio_transporte'      => 'medio de transporte',
            'gastos_alimentacion'   => 'gastos de alimentación',
            'gastos_alojamiento'    => 'gastos de alojamiento',
            'actividades'           => 'actividades',
            'actividades.*.mount'   => 'monto',
            'actividades.*.rinde_gastos_servicio' => 'rinde gasto servicio',
            'n_dias_40'             => 'n° de días al 40%',
            'n_dias_100'            => 'n° de días al 100%',
            'observacion_pasajes'   => 'observación de pasajes',
            'n_contacto'            => 'N° de contacto',
            'email'                 => 'correo'
        ];
    }
}
