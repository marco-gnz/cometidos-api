@component('mail::message')
# Actualización Informe de Cometido

Estimado/a {{ $last_status->informeCometido->userBy->nombres}} {{ $last_status->informeCometido->userBy->apellidos}},

El informe de cometido en la solicitud con número de resolución <strong>{{ $last_status->informeCometido->solicitud->codigo }}</strong>, ha sido {{ App\Models\EstadoInformeCometido::STATUS_NOM[$last_status->status] }}. A continuación, encontrará un resumen de su informe de cometido:

@component('vendor.mail.html.panel')

@slot('content')
    <table class="table">
        <tr>
            <th>Periodo del Cometido:</th>
            <td>{{ \Carbon\Carbon::parse($last_status->informeCometido->fecha_inicio)->format('d-m-Y') }} / {{ \Carbon\Carbon::parse($last_status->informeCometido->fecha_termino)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <th>Hora de Salida / Llegada:</th>
            <td>{{ \Carbon\Carbon::parse($last_status->informeCometido->hora_llegada)->format('H:i') }} / {{ \Carbon\Carbon::parse($last_status->informeCometido->hora_salida)->format('H:i') }} hrs.</td>
        </tr>
        <tr>
            <th>Utiliza Transporte:</th>
            <td>{{ $last_status->informeCometido->utiliza_transporte ? 'Sí' : 'No' }}</td>
        </tr>
        @if ($last_status->informeCometido->utiliza_transporte)
            <tr>
                <th>Medio de Transporte:</th>
                <td>{{ $last_status->informeCometido->transportes ? $last_status->informeCometido->transportes->pluck('nombre')->implode(', ') : '' }}</td>
            </tr>
        @endif
        <tr>
            <th>Movimiento:</th>
            <td>{{ $last_status->perfil ? $last_status->perfil->name : '' }} - {{ $last_status->userBy->nombre_completo }}</td>
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

@component('mail::button', ['url' => config('app.frontend_url') . '/mi-cuenta/solicitudes', 'color' => 'primary'])
    Ver Mis Solicitudes de Cometido
@endcomponent

Saludos cordiales,

{{ config('app.name') }}
@endcomponent
