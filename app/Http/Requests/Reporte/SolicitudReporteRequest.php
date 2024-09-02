<?php

namespace App\Http\Requests\Reporte;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudReporteRequest extends FormRequest
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
            'columns'       => ['required', 'array'],
            'year'          => ['required'],
            'months'        => ['required'],
            'filename'      => ['required']
        ];
    }

    public function messages()
    {
        return [
            'columns.required'                      => 'Las :attribute son requeridas',

            'year.required'                         => 'El :attribute es obligatorio',

            'months.required'                       => 'El o los :attribute son obligatorios',
        ];
    }

    public function attributes()
    {
        return [
            'columns'           => 'columnas',
            'year'              => 'año',
            'months'            => 'meses'
        ];
    }
}
