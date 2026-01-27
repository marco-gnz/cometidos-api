@component('mail::message')
# Actualización Proceso de Rendición de Gastos

Estimado/a {{ $last_status->procesoRendicionGasto->userBy->nombres}} {{ $last_status->procesoRendicionGasto->userBy->apellidos}},

El proceso de rendición de gastos con el número de folio <strong>{{ $last_status->procesoRendicionGasto->n_folio }}</strong>, ha sido {{ App\Models\EstadoProcesoRendicionGasto::STATUS_NOM[$last_status->status] }}. A continuación, encontrará un resumen del proceso de rendición de gastos:

@component('vendor.mail.html.panel')

@slot('content')
    <table class="table">
        <tr>
            <th>N° Resolución Cometido</th>
            <td>{{$last_status->procesoRendicionGasto->solicitud->codigo}}</td>
        </tr>
        <tr>
            <th>Items Rendidos</th>
            <td>{{$last_status->procesoRendicionGasto->nomRendicionesSolicitadas()}}</td>
        </tr>
        <tr>
            <th>Monto Total Rendido</th>
            <td>{{$last_status->procesoRendicionGasto->sumRendicionesSolicitadas()}}</td>
        </tr>
        @if (App\Models\EstadoProcesoRendicionGasto::STATUS_APROBADO_N === $last_status->status || App\Models\EstadoProcesoRendicionGasto::STATUS_APROBADO_S === $last_status->status)
            <tr>
                <th>Monto Total Aprobado</th>
                <td>{{$last_status->procesoRendicionGasto->sumRendicionesAprobadas()}}</td>
            </tr>
        @endif
        <tr>
            <th>Movimiento:</th>
            <td>{{ $last_status->perfil ? $last_status->perfil->name : '' }}  {{ $last_status->userBy ? $last_status->userBy->nombre_completo : '' }}</td>
        </tr>
        <tr>
            <th>Fecha Movimiento:</th>
            <td>{{ \Carbon\Carbon::parse($last_status->fecha_by_user)->format('d-m-Y H:i:s') }}</td>
        </tr>
        <tr>
            <th>Observación:</th>
            <td>{{ $last_status->observacion ? $last_status->observacion : '' }}</td>
        </tr>
    </table>
@endslot
@endcomponent

@component('mail::button', ['url' => config('app.frontend_url') . '/mi-cuenta/rendiciones', 'color' => 'primary'])
    Ver Mis Rendiciones de Gasto
@endcomponent

Saludos cordiales,

{{ config('app.name') }}
@endcomponent

