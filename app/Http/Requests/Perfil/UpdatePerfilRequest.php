<?php

namespace App\Http\Requests\Perfil;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerfilRequest extends FormRequest
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
            'perfiles_id'           => ['required', 'array'],
            'establecimientos_id'   => ['required', 'array'],
            'leys_id'               => ['required', 'array'],
            'medios_transporte_id'  => ['nullable', 'array'],
            'deptos_id'             => ['nullable', 'array'],
            'permissions_id'        => ['nullable', 'array']
        ];
    }

    public function messages()
    {
        return [
            'user_id.required'                  => 'El :attribute es obligatorio',

            'perfiles_id.required'              => 'El :attribute es obligatorio',

            'establecimientos_id.required'      => 'El :attribute es obligatorio',

            'leys_id.required'                  => 'La :attribute es obligatoria',

            'medios_transporte_id.required'     => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'user_id'                   => 'funcionario',
            'perfiles_id'               => 'perfil',
            'establecimientos_id'       => 'establecimiento',
            'leys_id'                   => 'ley',
            'medios_transporte_id'      => 'medio de transporte',
            'deptos_id'                 => 'depto',
            'permissions_id'            => 'permiso',
        ];
    }
}
