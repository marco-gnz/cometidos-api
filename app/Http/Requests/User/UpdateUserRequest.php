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
}
