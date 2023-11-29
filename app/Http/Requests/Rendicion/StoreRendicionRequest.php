<?php

namespace App\Http\Requests\Rendicion;

use Illuminate\Foundation\Http\FormRequest;

class StoreRendicionRequest extends FormRequest
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
            'solicitud_uuid'                            => 'required',
            'is_avion'                                  => 'required',
            'actividades'                               => ['present', 'required', 'array'],
            'actividades.*.id'                          => ['required'],
            'actividades.*.rinde_gasto'                 => ['required'],
            'actividades.*.mount'                       => ['required_if:actividades.*.rinde_gasto,true'],
            'actividades.*.rinde_gastos_servicio' => [
                function ($attribute, $value, $fail) {
                    $medio_transporte               = request()->input('medio_transporte');
                    $index                          = preg_replace('/[^0-9]/', '', $attribute);
                    $rinde_gasto                    = "actividades.{$index}.rinde_gasto";
                    $id_actividad                   = "actividades.{$index}.id";
                    $rinde_gasto_value              = request()->input($rinde_gasto);
                    $actividad_id_value             = request()->input($id_actividad);
                    $rinde_gastos_servicio_value    = request()->input($attribute);

                    if(($rinde_gasto_value != 1 && $actividad_id_value === 1 && $rinde_gastos_servicio_value === null)){
                        $fail("Respuesta es obligatoria");
                    }
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            'actividades.required'                      => 'La :attribute es obligatoria',

            'actividades.*.rinde_gasto.required'        => 'El :attribute es obligatorio',
            'actividades.*.mount.required_if'           => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'actividades'           => 'actividad',
            'actividades.*.mount'   => 'monto',
        ];
    }
}
