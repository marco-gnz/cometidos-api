<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;

class AnularSolicitudUserRequest extends FormRequest
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
            'solicitud_uuid'    => ['required', 'exists:solicituds,uuid'],
            'observacion'       => ['required', 'max:250']
        ];
    }

    public function messages()
    {
        return [
            'observacion.required'   => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'observacion'           => 'observación',
        ];
    }
}
