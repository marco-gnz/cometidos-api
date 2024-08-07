<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;

class ValidateFileSolicitudRequest extends FormRequest
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
            'uuid'  => 'nullable',
            'file' => 'nullable|mimes:pdf,jpg,png,jpeg,doc,docx,eml|max:5000',
        ];
    }

    public function messages()
    {
        return [
            'file.mimes' => 'El :attribute debe ser un archivo de tipo .pdf,.jpg,.png,.jpeg,.doc,.docx,.eml',
            'file.max'   => 'Por el momento, el :attribute debe ser menor a :max kilobytes (5 megabytes)',
        ];
    }

    public function attributes()
    {
        return [
            'file' => 'archivo'
        ];
    }
}
