<?php

namespace App\Http\Requests\ProcesoRendicion;

use Illuminate\Foundation\Http\FormRequest;

class StatusProcesoRendicionRequest extends FormRequest
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
            'uuid'          => ['required', 'exists:proceso_rendicion_gastos,uuid'],
            'status'        => ['required'],
            'observacion'   => ['required_unless:status,2']
        ];
    }

    public function messages()
    {
        return [
            'observacion.required_unless'       => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'observacion'           => 'observación',
        ];
    }
}
