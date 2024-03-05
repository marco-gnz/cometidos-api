<?php

namespace App\Http\Requests\Informe;

use Illuminate\Foundation\Http\FormRequest;

class StatusInformeRequest extends FormRequest
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
            'uuid'              => ['required', 'exists:informe_cometidos,uuid'],
            'status'            => ['required'],
            'observacion'       => ['required_if:status,2']
        ];
    }

    public function messages()
    {
        return [
            'observacion.required_if'          => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'observacion'   => 'observación'
        ];
    }

}
