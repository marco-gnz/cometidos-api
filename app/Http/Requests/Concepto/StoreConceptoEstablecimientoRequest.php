<?php

namespace App\Http\Requests\Concepto;

use Illuminate\Foundation\Http\FormRequest;

class StoreConceptoEstablecimientoRequest extends FormRequest
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
            'concepto_id'                   => ['required'],
            'concepto_establecimiento_id'   => ['required'],
            'user_selected_id'              => ['required'],
            'role_id'                       => ['required_if:concepto_id,2']
        ];
    }

    public function messages()
    {
        return [
            'concepto_establecimiento_id.required'  =>  'El :attribute es obligatorio',

            'user_selected_id.required'             =>  'El :attribute es obligatorio',

            'role_id.required_if'                   =>  'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'concepto_establecimiento_id'   => 'concepto',
            'user_selected_id'              => 'usuario',
            'role_id'                       => 'tipo de usuario'
        ];
    }
}
