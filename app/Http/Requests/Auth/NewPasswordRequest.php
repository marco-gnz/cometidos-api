<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class NewPasswordRequest extends FormRequest
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
            'token'                     => ['required'],
            'email'                     => ['required', 'email'],
            'password'                  => ['required', 'min:3'],
            'password_confirmation'     => ['required', 'min:3', 'same:password']
        ];
    }

    public function messages()
    {
        return [
            'token.required'                        => 'El :attribute es obligatorio',

            'email.required'                        => 'El :attribute es obligatorio',
            'email.email'                           => 'El :attribute debe ser un correo válido',

            'password.required'                     => 'La :attribute es obligatoria',
            'password.min'                          => 'La :attribute debe tener mínimo :min carácteres',

            'password_confirmation.required'         => 'La :attribute es obligatoria',
            'password_confirmation.min'              => 'La :attribute debe tener mínimo :min carácteres',
            'password_confirmation.same'             => 'La :attribute debe ser idéntica a la nueva contraseña',

        ];
    }

    public function attributes()
    {
        return [
            'email'                     => 'correo',
            'password'              => 'nueva contraseña',
            'password_confirmation'      => 'confirmación de nueva contraseña',
        ];
    }
}
