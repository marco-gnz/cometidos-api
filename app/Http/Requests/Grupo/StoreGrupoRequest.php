<?php

namespace App\Http\Requests\Grupo;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrupoRequest extends FormRequest
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
            'establecimiento_id'            => ['required'],
            'departamento_id'               => ['required'],
            'sub_departamento_id'           => ['required'],
            'firmantes'                     => ['present', 'required', 'array'],
            'firmantes.*.id'                => ['required'],
            'firmantes.*.role_id'           => ['required']
        ];
    }

    public function messages()
    {
        return [
            'establecimiento_id.required'           => 'El :attribute es obligatorio',

            'departamento_id.required'              => 'El :attribute es obligatorio',

            'sub_departamento_id.required'          => 'El :attribute es obligatorio',

            'firmantes.required'                    => 'El :attribute es obligatorio',
            'firmantes.*.role_id.required'          => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'establecimiento_id'    => 'establecimiento',
            'departamento_id'       => 'departamento',
            'sub_departamento_id'   => 'sub departamento',
            'firmantes'             => 'firmante',
            'firmantes.*.role_id'   => 'perfil'
        ];
    }
}
