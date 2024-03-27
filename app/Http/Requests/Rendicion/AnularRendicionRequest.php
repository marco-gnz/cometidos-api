<?php

namespace App\Http\Requests\Rendicion;

use Illuminate\Foundation\Http\FormRequest;

class AnularRendicionRequest extends FormRequest
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
            'observacion'   => ['required']
        ];
    }

    public function messages()
    {
        return [
            'observacion.required'                      => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'observacion'           => 'observación',
        ];
    }
}
