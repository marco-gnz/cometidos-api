<?php

namespace App\Http\Requests\Rendicion;

use Illuminate\Foundation\Http\FormRequest;

class StatusRendicionRequest extends FormRequest
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
            'status'        => ['required', 'in:0,1,2'],
            'mount_real'    => ['required', 'integer'],
            'observacion'   => ['required_if:status,2']
        ];
    }

    public function messages()
    {
        return [
            'mount_real.required'   => 'El :attribute es obligatorio',
            'mount_real.integer'    => 'El :attribute debe ser un valor numérico',

            'observacion.required_if'   => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'mount_real'            => 'monto',
            'observacion'           => 'observación',
        ];
    }
}
