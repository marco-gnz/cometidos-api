<?php

namespace App\Http\Requests\Ausentismo;

use Illuminate\Foundation\Http\FormRequest;

class StoreAusentismoRequest extends FormRequest
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
            'subrogantes_id'            => ['required', 'array']
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

            'subrogantes_id.required'               => 'El :attribute debe ser obligatorio'
        ];
    }

    public function attributes()
    {
        return [
            'fecha_inicio'      => 'fecha',
            'fecha_termino'     => 'fecha',
            'subrogantes_id'    => 'subrogante'
        ];
    }
}
