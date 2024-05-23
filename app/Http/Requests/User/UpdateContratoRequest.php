<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContratoRequest extends FormRequest
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
            'uuid'                      => ['required', 'exists:contratos,uuid'],
            'establecimiento_id'        => ['required'],
            'departamento_id'           => ['required'],
            'sub_departamento_id'       => ['required'],
            'estamento_id'              => ['required'],
            'cargo_id'                  => ['required'],
            'calidad_id'                => ['required'],
            'hora_id'                   => ['required'],
            'ley_id'                    => ['required'],
            'grado_id'                  => ['required_unless:ley_id,4']
        ];
    }

    public function messages()
    {
        return [
            'establecimiento_id.required'       => 'El :attribute es obligatorio',

            'departamento_id.required'          => 'El :attribute es obligatorio',

            'sub_departamento_id.required'      => 'El :attribute es obligatorio',

            'estamento_id.required'             => 'El :attribute es obligatorio',

            'cargo_id.required'                 => 'El :attribute es obligatorio',

            'calidad_id.required'               => 'La :attribute es obligatoria',

            'hora_id.required'                  => 'La :attribute es obligatoria',

            'ley_id.required'                   => 'La :attribute es obligatoria',

            'grado_id.required_unless'          => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'establecimiento_id'    => 'establecimiento',
            'departamento_id'       => 'departamento',
            'sub_departamento_id'   => 'subdepartamento',
            'estamento_id'          => 'estamento',
            'cargo_id'              => 'cargo',
            'calidad_id'            => 'calidad',
            'hora_id'               => 'hora',
            'ley_id'                => 'ley',
            'grado_id'              => 'grado',
        ];
    }
}
