<?php

namespace App\Http\Requests\Perfil;

use Illuminate\Foundation\Http\FormRequest;

class StorePerfilRequest extends FormRequest
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
            'user_id'               => ['required', 'exists:users,id'],
            'perfiles_id'           => ['required', 'array'],
            'establecimientos_id'   => ['required', 'array'],
            'leys_id'               => ['required', 'array'],
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
        ];
    }

    public function attributes()
    {
        return [
            'user_id'                   => 'funcionario',
            'perfiles_id'               => 'perfil',
            'establecimientos_id'       => 'establecimiento',
            'leys_id'                   => 'ley',
            'deptos_id'                 => 'depto',
            'permissions_id'            => 'permiso',
        ];
    }
}
