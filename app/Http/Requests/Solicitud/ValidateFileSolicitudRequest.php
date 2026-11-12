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
            'file' => 'required|file|mimes:pdf,jpg,png,jpeg,doc,docx,eml|max:5000',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Seleccione un archivo para cargar.',
            'file.file' => 'El archivo no es válido. Vuelva a guardarlo e intente nuevamente.',
            'file.uploaded' => 'El archivo no se recibió correctamente. Intente cargarlo nuevamente.',
            'file.mimes' => 'El contenido del archivo no corresponde a un formato permitido o está dañado. Use PDF, JPG, PNG, DOC, DOCX o EML; vuelva a guardarlo o exportarlo e intente nuevamente.',
            'file.max'   => 'Por el momento, el :attribute debe ser menor a :max kilobytes (5 megabytes)',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('file')) return;
            try {
                app(\App\Services\Archivos\ValidarArchivo::class)->validar($this->file('file'));
            } catch (\Illuminate\Validation\ValidationException $error) {
                foreach ($error->errors()['file'] as $mensaje) $validator->errors()->add('file', $mensaje);
            }
        });
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }

    public function attributes()
    {
        return [
            'file' => 'archivo'
        ];
    }
}
