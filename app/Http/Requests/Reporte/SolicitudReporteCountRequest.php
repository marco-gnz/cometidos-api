<?php

namespace App\Http\Requests\Reporte;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudReporteCountRequest extends FormRequest
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
            'year'          => ['required'],
            'month'         => ['required']
        ];
    }

    public function messages()
    {
        return [
            'year.required'                         => 'El :attribute es obligatorio',

            'month.required'                        => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'year'              => 'año',
            'month'             => 'mes'
        ];
    }
}
