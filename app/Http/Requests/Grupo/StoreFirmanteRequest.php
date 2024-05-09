<?php

namespace App\Http\Requests\Grupo;

use Illuminate\Foundation\Http\FormRequest;

class StoreFirmanteRequest extends FormRequest
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
            'grupo_uuid'        => ['required', 'exists:grupos,uuid'],
            'funcionario_id'    => ['required', 'exists:users,id'],
            'perfil_id'         => ['required']
        ];
    }

    public function messages()
    {
        return [
            'grupo_uuid.required'           => 'El :attribute es obligatorio',

            'funcionario_id.required'           => 'El :attribute es obligatorio',

            'perfil_id.required'           => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'grupo_uuid'        => 'grupo',
            'funcionario_id'    => 'funcionario',
            'perfil_id'         => 'perfil'
        ];
    }
}
