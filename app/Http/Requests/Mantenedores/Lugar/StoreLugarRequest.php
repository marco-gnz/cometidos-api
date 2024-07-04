<?php

namespace App\Http\Requests\Mantenedores\Lugar;

use Illuminate\Foundation\Http\FormRequest;

class StoreLugarRequest extends FormRequest
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
            'nombre'    => ['required', 'unique:lugars,nombre', 'max:50']
        ];
    }

    public function messages()
    {
        return [
            'nombre.required'                   => 'El :attribute es obligatorio',
            'nombre.unique'                     => 'El :attribute ya existe en el sistema',
            'nombre.max'                        => 'La :attribute son máximo :max carácteres',
        ];
    }

    public function attributes()
    {
        return [
            'nombre'          => 'nombre de lugar'
        ];
    }
}
