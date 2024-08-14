<?php

namespace App\Http\Requests\User;

use App\Rules\RutDvValidateRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'rut'                       => ['required', 'min:7', 'max:8', Rule::unique('users', 'rut'), new RutDvValidateRule($this->dv)],
            'dv'                        => ['required', 'min:1', 'max:1'],
            'nombres'                   => ['required'],
            'apellidos'                 => ['required'],
            'email'                     => ['required', 'email', Rule::unique('users', 'rut')],
            'is_contrato'               => ['required', 'boolean'],
            'establecimiento_id'        => ['required_if:is_contrato,true'],
            'departamento_id'           => ['required_if:is_contrato,true'],
            'sub_departamento_id'       => ['required_if:is_contrato,true'],
            'estamento_id'              => ['required_if:is_contrato,true'],
            'cargo_id'                  => ['required_if:is_contrato,true'],
            'calidad_id'                => ['required_if:is_contrato,true'],
            'hora_id'                   => ['required_if:is_contrato,true'],
            'ley_id'                    => ['required_if:is_contrato,true'],
            'grado_id'                  => ['required_if:is_contrato,true']
        ];
    }

    public function messages()
    {
        return [
            'rut.required'                      => 'El :attribute es obligatorio',
            'rut.min'                           => 'El :attribute son mínimo :min caracteres',
            'rut.max'                           => 'El :attribute son máximo :max caracteres',
            'rut.unique'                        => 'El :attribute ya existe en el sistema',

            'dv.required'                       => 'El :attribute es obligatorio',
            'dv.min'                            => 'El :attribute son mínimo :min caracter',
            'dv.max'                            => 'El :attribute son máximo :max caracter',

            'nombres.required'                  => 'El :attribute es obligatorio',

            'apellidos.required'                => 'El :attribute es obligatorio',

            'email.required'                    => 'El :attribute debe ser un email (Debe incluir @ y punto)',

            'establecimiento_id.required_if'       => 'El :attribute es obligatorio',

            'departamento_id.required_if'          => 'El :attribute es obligatorio',

            'sub_departamento_id.required_if'      => 'El :attribute es obligatorio',

            'estamento_id.required_if'             => 'El :attribute es obligatorio',

            'cargo_id.required_if'                 => 'El :attribute es obligatorio',

            'calidad_id.required_if'               => 'La :attribute es obligatoria',

            'hora_id.required_if'                  => 'La :attribute es obligatoria',

            'ley_id.required_if'                   => 'La :attribute es obligatoria',

            'grado_id.required_if'                 => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'rut'                   => 'rut',
            'dv'                    => 'DV',
            'nombres'               => 'nombre',
            'apellidos'             => 'apellido',
            'email'                 => 'correo',
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
