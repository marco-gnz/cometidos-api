<?php

namespace App\Http\Requests\Solicitud\Ajuste;

use Illuminate\Foundation\Http\FormRequest;

class StoreAjusteRequest extends FormRequest
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
            'calculo_uuid'      => ['exists:soliucitud_calculos,uuid'],
            'tipo_ajuste'       => ['required'],
            'n_dias_40'         => ['required_if:tipo_ajuste,0'],
            'n_dias_100'        => ['required_if:tipo_ajuste,0'],
            'monto_40'          => ['required_if:tipo_ajuste,1'],
            'monto_100'         => ['required_if:tipo_ajuste,1'],
            'observacion'       => ['required']
        ];
    }

    public function messages()
    {
        return [
            'tipo_ajuste.required'                      => 'El :attribute es obligatorio',

            'n_dias_40.required_if'                        => 'El :attribute es obligatorio',

            'n_dias_100.required_if'                       => 'El :attribute es obligatorio',

            'monto_40.required_if'                         => 'El :attribute es obligatorio',

            'monto_100.required_if'                        => 'El :attribute es obligatorio',

            'observacion.required'                      => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'tipo_ajuste'   => 'tipo de ajuste',
            'n_dias_40'     => 'n° de días al 40',
            'n_dias_100'    => 'n° de días al 100',
            'monto_40'      => 'monto al 40',
            'monto_100'     => 'monto al 100',
            'observacion'   => 'observación'
        ];
    }
}
