<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;

class StatusSolicitudRequest extends FormRequest
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
            'solicitud_uuid'    => ['exists:solicituds,uuid', 'required'],
            'status'            => ['required'],
            'observacion'       => ['required_unless:status,2'],
            'motivo_id'         => ['required_if:status,3'],
            'user_uuid'         => ['required_if:status,1'],
            'posicion_firma'    => ['required_if:status,1']
        ];
    }

    public function messages()
    {
        return [
            'observacion.required_unless'   => 'La :attribute es obligatoria',

            'motivo_id.required_if'         => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'observacion'           => 'observación',
            'motivo_id'             => 'motivo',
        ];
    }
}
