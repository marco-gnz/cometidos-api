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
        $rut                = '19270290';
        $user               = User::where('rut', $rut)->first();
        Auth::login($user);
        $motivos_cometido   = Motivo::orderBy('id', 'DESC')->get()->take(2);
        $tipo_comision      = TipoComision::find(1);
        $lugares            = Lugar::orderBy('id', 'DESC')->get()->take(2);
        $medio_transporte   = Transporte::orderBy('id', 'DESC')->get()->take(2);
        $fecha_inicio       = Carbon::now()->format('Y-m-d');
        $fecha_termino      = Carbon::now()->format('Y-m-d');
        $contrato           = $user->contratos()->first();
        Mail::fake();
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
            'utiliza_transporte'        => false,
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
            'contrato_uuid'             => $contrato->uuid,
            'observacion'               => $this->faker->text,
            'motivos_cometido'          => $motivos_cometido->pluck('id')->toArray(),
            'lugares_cometido'          => $lugares->pluck('id')->toArray(),
            'medio_transporte'          => $medio_transporte->pluck('id')->toArray()
        ];
        Log::info($data_request);
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
            $rut                = '19270290';
            $user               = User::where('rut', $rut)->first();
            Auth::login($user);
            $first_solicitud    = Solicitud::first();
            $motivos_cometido   = Motivo::all();
            $tipo_comision      = TipoComision::find(5);
            $lugares            = Lugar::orderBy('id', 'DESC')->get()->take(2);
            $medio_transporte   = Transporte::orderBy('id', 'DESC')->get()->take(2);
            $fecha_inicio       = Carbon::now()->format('Y-m-d');
            $fecha_termino      = Carbon::now()->addDay(3)->format('Y-m-d');
            Mail::fake();

            $data_request = [
                'solicitud_uuid'            => $first_solicitud->uuid,
                'fecha_inicio'              => $fecha_inicio,
                'fecha_termino'             => $fecha_termino,
                'hora_llegada'              => '08:00:00',
                'hora_salida'               => '17:00:00',
                'derecho_pago'              => true,
                'motivos_cometido'          => $motivos_cometido->pluck('id'),
                'tipo_comision_id'          => $tipo_comision ? $tipo_comision->id : null,
                'jornada'                   => Solicitud::JORNADA_TODO_EL_DIA,
                'dentro_pais'               => true,
                'lugares_cometido'          => $lugares->pluck('id'),
                'viaja_acompaniante'        => false,
                'alimentacion_red'          => false,
                'utiliza_transporte'        => true,
                'medio_transporte'          => $medio_transporte->pluck('id'),
                'actividad_realizada'       => $this->faker->text,
                'gastos_alimentacion'       => false,
                'gastos_alojamiento'        => false,
                'pernocta_lugar_residencia' => false,
                'n_dias_40'                 => 1,
                'n_dias_100'                => 0,
                'observacion_gastos'        => null,
            ];

            $is_files = true;
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
