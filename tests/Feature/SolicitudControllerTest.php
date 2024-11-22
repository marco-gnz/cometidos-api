<?php

namespace Tests\Feature;

use App\Http\Controllers\Solicitud\SolicitudController;
use App\Models\Lugar;
use App\Models\Motivo;
use App\Models\Solicitud;
use App\Models\TipoComision;
use App\Models\Transporte;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SolicitudControllerTest extends TestCase
{
    use WithFaker;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_store_solicitud()
    {
        $user               = User::whereHas('contratos', function($q){
            $q->where('establecimiento_id', 6);
        })->first();
        Auth::login($user);
        $motivos_cometido   = Motivo::orderBy('id', 'DESC')->get()->take(2);
        $tipo_comision      = TipoComision::find(5);
        $lugares            = Lugar::orderBy('id', 'DESC')->get()->take(2);
        $medio_transporte   = Transporte::orderBy('id', 'DESC')->get()->take(2);
        $fecha_inicio       = Carbon::now()->addDay(2)->format('Y-m-d');
        $fecha_termino      = Carbon::now()->addDay(2)->format('Y-m-d');
        $contrato           = $user->contratos()->first();
        //Mail::fake();
        //Event::fake(); //deshabilitar todo evento dispatch, incluso eventos en el modelo Solicitud-. created, creating, destroy, etc.

        /* Log::info($motivos_cometido->pluck('id')->toArray());
        Log::info($lugares->pluck('id')->toArray()); */
        $data_request = [
            'user_id'                   => $user->id,
            'fecha_inicio'              => $fecha_inicio,
            'fecha_termino'             => $fecha_termino,
            'hora_salida'               => '08:00:00',
            'hora_llegada'              => '13:00:00',
            'derecho_pago'              => true,
            'utiliza_transporte'        => true,
            'viaja_acompaniante'        => false,
            'alimentacion_red'          => false,
            'jornada'                   => Solicitud::JORNADA_TODO_EL_DIA,
            'dentro_pais'               => false,
            'tipo_comision_id'          => $tipo_comision->id,
            'actividad_realizada'       => $this->faker->text,
            'gastos_alimentacion'       => false,
            'gastos_alojamiento'        => false,
            'pernocta_lugar_residencia' => false,
            'n_dias_40'                 => 1,
            'n_dias_100'                => 0,
            'observacion_gastos'        => $this->faker->text,
            'contrato_uuid'             => $contrato ? $contrato->uuid : null,
            'observacion'               => $this->faker->text,
            'motivos_cometido'          => $motivos_cometido->pluck('id')->toArray(),
            'lugares_cometido'          => $lugares->pluck('id')->toArray(),
            'medio_transporte'          => $medio_transporte->pluck('id')->toArray(),
            'archivos'                  => null
        ];

        $response = $this->postJson(action([SolicitudController::class, 'storeSolicitud']), $data_request);

        $response->assertStatus(200);

        $this->assertDatabaseHas('solicituds', [
            'user_id'       => $data_request['user_id'],
            'fecha_inicio'  => $data_request['fecha_inicio'],
        ]);
    }

    public function test_update_solicitud()
    {
        try {
            $codigo = 6771;
            $first_solicitud    = Solicitud::where('codigo', 'like', '%' . $codigo . '%')->first();
            Auth::login($first_solicitud->funcionario);
            Log::info($first_solicitud->codigo);
            $motivos_cometido   = Motivo::orderBy('id', 'DESC')->get()->take(2);
            $tipo_comision      = TipoComision::find(5);
            $lugares            = Lugar::orderBy('id', 'DESC')->get()->take(2);
            $medio_transporte   = Transporte::orderBy('id', 'DESC')->get()->take(2);
            $fecha_inicio       = Carbon::now()->format('Y-m-d');
            $fecha_termino      = Carbon::now()->addDay(3)->format('Y-m-d');
            Mail::fake();

            $data_request = [
                'solicitud_uuid'            => $first_solicitud->uuid,
                'fecha_inicio'              => $first_solicitud->fecha_inicio,
                'fecha_termino'             => $first_solicitud->fecha_termino,
                'hora_salida'               => '08:00:00',
                'hora_llegada'              => '13:00:00',
                'derecho_pago'              => true,
                'utiliza_transporte'        => true,
                'viaja_acompaniante'        => false,
                'alimentacion_red'          => false,
                'jornada'                   => Solicitud::JORNADA_TODO_EL_DIA,
                'dentro_pais'               => false,
                'tipo_comision_id'          => $tipo_comision->id,
                'actividad_realizada'       => $this->faker->text,
                'gastos_alimentacion'       => false,
                'gastos_alojamiento'        => false,
                'pernocta_lugar_residencia' => false,
                'n_dias_40'                 => 1,
                'n_dias_100'                => 0,
                'observacion_gastos'        => $first_solicitud->observacion_gastos,
                'observacion'               => $first_solicitud->observacion,
                'motivos_cometido'          => $motivos_cometido->pluck('id')->toArray(),
                'lugares_cometido'          => $lugares->pluck('id')->toArray(),
                'medio_transporte'          => $medio_transporte->pluck('id')->toArray(),
                'archivos'                  => null
            ];

            $is_files = false;
            if ($is_files) {
                $response = $this->withHeaders([
                    'Content-Type' => 'multipart/form-data',
                ])->postJson(action([SolicitudController::class, 'updateSolicitud']), array_merge($data_request, [
                    'archivos' => [
                        UploadedFile::fake()->create('file1.pdf'),
                        UploadedFile::fake()->create('file2.pdf'),
                    ]
                ]));
            } else {
                $response = $this->postJson(action([SolicitudController::class, 'updateSolicitud']), $data_request);
            }

            $response->assertStatus(200);
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }
}
