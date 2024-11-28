<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstitucionalDocumentoRequest extends FormRequest
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
            'nombre'                => 'required|max:100',
            'observacion'           => 'nullable|max:255',
            'file'                  => 'required|mimes:pdf,jpg,png,jpeg,doc,docx,eml|max:5000',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required'   => 'El :attribute es obligatorio',
            'nombre.max'        => 'El :attribute son máximo :max carácteres',

            'observacion.max'   => 'La :attribute son máximo :max carácteres',

            'file.mimes' => 'El :attribute debe ser un archivo de tipo .pdf,.jpg,.png,.jpeg,.doc,.docx,.eml',
            'file.max'   => 'Por el momento, el :attribute debe ser menor a :max kilobytes (5 megabytes)',
        ];
    }

    public function attributes()
    {
        return [
            'nombre'        => 'nombre',
            'observacion'   => 'observación',
            'file'          => 'archivo'
        ];
    }
}
