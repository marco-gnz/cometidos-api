<?php

namespace App\Http\Requests\ProcesoRendicion;

use App\Models\Solicitud;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcesoRendicionRequest extends FormRequest
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
        $solicitud = Solicitud::where('uuid', $this->solicitud_uuid)->first();
        return [
            'solicitud_uuid'                            => ['required'],
            'actividades'                               => ['present', 'required', 'array'],
            'actividades.*.id'                          => ['required'],
            'actividades.*.rinde_gasto'                 => ['required', 'in:0,1'],
            'actividades.*.mount'                       => ['required_if:actividades.*.rinde_gasto,1'],
            'actividades.*.rinde_gastos_servicio'       => [
                function ($attribute, $value, $fail) use ($solicitud) {
                    $actividades = $this->input('actividades', []);
                    $is_avion = $solicitud->transportes()->where('solicitud_transporte.transporte_id', 1)->exists();

                    foreach ($actividades as $index => $actividad) {
                        if ($actividad['id'] == 1 && $actividad['rinde_gastos_servicio'] === null && $is_avion) {
                            if ($index === 0) {
                                $fail('Es obligatorio cuando la actividad de transporte es Avión.');
                            }
                        }
                    }
                },
            ],
            'archivos'                                  => ['required'],
            'observacion'                               => [
                function ($attribute, $value, $fail) {
                    $actividades = $this->input('actividades', []);

                    foreach ($actividades as $actividad) {
                        if ($actividad['id'] === 12 && $actividad['rinde_gasto'] === 1 && $actividad['mount'] > 0) {
                            if (empty($value)) {
                                $fail('El campo observacion es obligatorio cuando seleccione OTROS.');
                            }
                        }
                    }
                },
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $actividades = $this->input('actividades', []);
            $hasRindeGasto = false;

            foreach ($actividades as $actividad) {
                if (isset($actividad['rinde_gasto']) && $actividad['rinde_gasto'] == 1) {
                    $hasRindeGasto = true;
                    break;
                }
            }

            if (!$hasRindeGasto) {
                $validator->errors()->add('actividades', 'Debe haber al menos una actividad rendida.');
            }
        });
    }

    public function messages()
    {
        return [
            'actividades.*.rinde_gasto.required'           => 'La :attribute es obligatoria',
            'actividades.*.mount.required_if'              => 'El :attribute es obligatorio',

            'archivos.required'                             => 'Los :attribute son obligatorios',
        ];
    }

    public function attributes()
    {
        return [
            'actividades.*.rinde_gasto'     => 'actividad',
            'actividades.*.mount'            => 'monto',
            'archivos'                      => 'archivos'
        ];
    }

}
