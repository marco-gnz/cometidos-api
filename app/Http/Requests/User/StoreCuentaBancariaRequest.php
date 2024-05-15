<?php

namespace App\Http\Requests\User;

use App\Models\CuentaBancaria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCuentaBancariaRequest extends FormRequest
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
            'tipo_cuenta'   => ['required'],
            'n_cuenta' => [
                'required_unless:tipo_cuenta,6',
                function ($attribute, $value, $fail) {
                    if (request()->input('tipo_cuenta') != 6 && !is_null($value)) {
                        $exists = \DB::table('cuenta_bancarias')
                            ->where('n_cuenta', $value)
                            ->exists();
                        if ($exists) {
                            $fail('El número de cuenta ya existe en el sistema.');
                        }
                    }
                },
            ],
            'banco_id'      => ['required_unless:tipo_cuenta,6'],
            'user_uuid'     => ['required']
        ];
    }

    public function messages()
    {
        return [
            'n_cuenta.required_unless'               => 'El :attribute es obligatorio',
            'n_cuenta.unique'                        => 'El :attribute ya existe en el sistema',

            'tipo_cuenta.required'                  => 'El :attribute es obligatorio',

            'banco_id.required_unless'               => 'El :attribute es obligatorio',

        ];
    }

    public function attributes()
    {
        return [
            'n_cuenta'                   => 'N° de cuenta',
            'tipo_cuenta'                => 'tipo de cuenta',
            'banco_id'                   => 'banco'
        ];
    }
}
