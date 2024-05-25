<?php

namespace App\Http\Requests\Convenio;

use Illuminate\Foundation\Http\FormRequest;

class StoreConvenioRequest extends FormRequest
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
            'fecha_inicio'          => ['required', 'date'],
            'fecha_termino'         => ['required', 'date'],
            'fecha_resolucion'      => ['nullable'],
            'n_resolucion'          => ['nullable'],
            'n_viatico_mensual'     => ['required'],
            'anio'                  => ['required'],
            'observacion'           => ['nullable', 'max:255'],
            'estamento_id'          => ['required'],
            'ley_id'                => ['required'],
            'establecimiento_id'    => ['required'],
            'ilustre_id'            => ['required'],
            'user_id'               => ['required']
        ];
    }

    public function messages()
    {
        return [
            'fecha_inicio.required'     => 'La :attribute es obligatoria',

            'fecha_termino.required'     => 'La :attribute es obligatoria',

            'fecha_resolucion.required'     => 'La :attribute es obligatoria',

            'n_resolucion.required'     => 'El :attribute es obligatorio',

            'n_viatico_mensual.required'     => 'El :attribute es obligatorio',

            'anio.required'                 => 'El :attribute es obligatorio',

            'estamento_id.required'     => 'El :attribute es obligatorio',

            'ley_id.required'     => 'La :attribute es obligatoria',

            'establecimiento_id.required'     => 'El :attribute es obligatorio',

            'ilustre_id.required'     => 'El :attribute es obligatorio',

            'user_id.required'     => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'fecha_inicio'          => 'fecha de inicio',
            'fecha_termino'         => 'fecha de término',
            'fecha_resolucion'      => 'fecha de resolución',
            'n_resolucion'          => 'n° de resolución',
            'n_viatico_mensual'     => 'n° de viáticos',
            'anio'                  => 'año',
            'observacion'           => 'observación',
            'estamento_id'          => 'estamento',
            'ley_id'                => 'ley',
            'establecimiento_id'    => 'establecimiento',
            'ilustre_id'            => 'ilustre',
            'user_id'               => 'funcionario'
        ];
    }
}
