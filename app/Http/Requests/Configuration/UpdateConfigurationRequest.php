<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationRequest extends FormRequest
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
            'valor' => ['required']
        ];
    }

    public function messages()
    {
        return [
            'valor.required'           => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'valor'        => 'valor',
        ];
    }
}
