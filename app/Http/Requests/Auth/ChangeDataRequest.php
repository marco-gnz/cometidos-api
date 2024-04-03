<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangeDataRequest extends FormRequest
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
            'observacion'   => ['required', 'max:255']
        ];
    }

    public function messages()
    {
        return [
            'observacion.required'                      => 'La :attribute es obligatoria',
            'observacion.max'                           => 'La :attribute son máximo :max carácteres',
        ];
    }

    public function attributes()
    {
        return [
            'observacion'                  => 'observación'
        ];
    }
}
