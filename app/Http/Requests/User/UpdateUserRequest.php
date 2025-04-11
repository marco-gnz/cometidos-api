<?php

namespace App\Http\Requests\User;

use App\Rules\RutDvValidateRule;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'id'                        => ['required', 'exists:users,id'],
            'rut'                       => ['required', 'min:7', 'max:8', Rule::unique('users', 'rut')->ignore($this->id), new RutDvValidateRule($this->dv)],
            'dv'                        => ['required', 'min:1', 'max:1'],
            'nombres'                   => ['required'],
            'apellidos'                 => ['required'],
            'email'                     => ['required', 'email', Rule::unique('users', 'rut')->ignore($this->id)],
            'fecha_nacimiento'          => ['required', 'date', 'before_or_equal:' . now()->subYears(15)->format('Y-m-d')],
            'nacionalidad_id'           => ['required', 'exists:nacionalidads,id']
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

            'nacionalidad_id.required'         => 'La :attribute es obligatoria',

            'fecha_nacimiento.required'         => 'La :attribute es obligatoria',
            'fecha_nacimiento.before_or_equal'  => 'La fecha de nacimiento debe ser de una persona mayor de 15 años.',
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
            'fecha_nacimiento'      => 'fecha de nacimiento',
            'nacionalidad_id'       => 'nacionalidad'
        ];
    }
}
