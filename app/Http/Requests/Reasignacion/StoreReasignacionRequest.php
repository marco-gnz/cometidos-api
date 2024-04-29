<?php

namespace App\Http\Requests\Reasignacion;

use Illuminate\Foundation\Http\FormRequest;

class StoreReasignacionRequest extends FormRequest
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
            'firmante_uuid'             => ['required', 'exists:users,uuid'],
            'fecha_inicio'              => ['required', 'date', 'before_or_equal:fecha_termino'],
            'fecha_termino'             => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'subrogante_uuid'            => ['required'],
            'solicitudes_id'            => ['required', 'array']
        ];
    }

    public function messages()
    {
        return [
            'firmante_uuid.required'                => 'El :attribute es obligatorio',
            'fecha_inicio.required'                 => 'La :attribute es obligatoria',
            'fecha_inicio.date'                     => 'La :attribute debe ser una fecha válida',
            'fecha_inicio.before_or_equal'          => 'La :attribute debe ser anterior a fecha de término',

            'fecha_termino.required'                => 'La :attribute es obligatoria',
            'fecha_termino.date'                    => 'La :attribute debe ser una fecha válida',
            'fecha_termino.after_or_equal'          => 'La :attribute debe ser superior a fecha de inicio',

            'subrogante_uuid.required'               => 'El :attribute es obligatorio',

            'solicitudes_id.required'               => 'La :attribute es obligatoria'
        ];
    }

    public function attributes()
    {
        return [
            'firmante_uuid'     => 'firmante',
            'subrogante_uuid'    => 'subrogante',
            'fecha_inicio'      => 'fecha',
            'fecha_termino'     => 'fecha',
            'solicitudes_id'    => 'solicitud'
        ];
    }
}
