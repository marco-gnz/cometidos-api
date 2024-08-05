<!-- resources/views/vendor/mail/html/message.blade.php -->
@component('mail::message')
# Actualización de Solicitud de Cometido

Estimado/a {{ $solicitud->funcionario->nombres}} {{ $solicitud->funcionario->apellidos}},

La solicitud de cometido con el número de resolución <strong>{{ $solicitud->codigo }}</strong>, ha sido {{ App\Models\EstadoSolicitud::STATUS_NOM[$last_status->status] }}. A continuación, encontrará un resumen de la solicitud:

@component('vendor.mail.html.panel')
@slot('content')
    <table class="table">
        <tr>
            <th>Periodo del Cometido:</th>
            <td>{{ \Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d-m-Y') }} / {{ \Carbon\Carbon::parse($solicitud->fecha_termino)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <th>Hora de Salida / Llegada:</th>
            <td>{{ \Carbon\Carbon::parse($solicitud->hora_llegada)->format('H:i') }} / {{ \Carbon\Carbon::parse($solicitud->hora_salida)->format('H:i') }} hrs.</td>
        </tr>
        <tr>
            <th>Derecho a Viático:</th>
            <td>{{ $solicitud->derecho_pago ? 'Sí' : 'No' }}</td>
        </tr>
        <tr>
            <th>Destino de cometido:</th>
            <td>
                @if (!$solicitud->dentro_pais)
                    {{ $solicitud->lugares->pluck('nombre')->implode(', ') }}
                @else
                    {{ $solicitud->paises->pluck('nombre')->implode(', ') }}
                @endif
            </td>
        </tr>
        <tr>
            <th>Tipo de Comisión:</th>
            <td>{{ $solicitud->tipoComision->nombre }}</td>
        </tr>
        <tr>
            <th>Motivo de cometido:</th>
            <td>{{ $solicitud->motivos ? $solicitud->motivos->pluck('nombre')->implode(', ') : '' }}</td>
        </tr>
        <tr>
            <th>Utiliza Transporte:</th>
            <td>{{ $solicitud->utiliza_transporte ? 'Sí' : 'No' }}</td>
        </tr>
        @if ($solicitud->utiliza_transporte)
            <tr>
                <th>Medio de Transporte:</th>
                <td>{{ $solicitud->transportes ? $solicitud->transportes->pluck('nombre')->implode(', ') : '' }}</td>
            </tr>
        @endif
        @if ($last_status->funcionario && !$last_status->movimiento_system)
        <tr>
            <th>Movimiento:</th>
            <td>{{ $last_status->perfil ? $last_status->perfil->name : '' }} - {{ $last_status->funcionario->nombre_completo }}</td>
        </tr>
        @endif
        <tr>
            <th>Fecha Movimiento:</th>
            <td>{{ \Carbon\Carbon::parse($last_status->created_at)->format('d-m-Y H:i:s') }}</td>
        </tr>
        @if (App\Models\EstadoSolicitud::STATUS_RECHAZADO === $last_status->status)
            <tr>
                <th>Motivo Rechazo</th>
                <td>{{ App\Models\EstadoSolicitud::RECHAZO_NOM[$last_status->motivo_rechazo] }}</td>
            </tr>
        @endif
        <tr>
            <th>Observación:</th>
            <td>{{ $last_status->observacion ? $last_status->observacion : '' }}</td>
        </tr>
    </table>
@endslot
@endcomponent

@if (App\Models\EstadoSolicitud::STATUS_APROBADO === $last_status->status)
@component('mail::button', ['url' => config('app.frontend_url') . '/mi-cuenta/solicitudes', 'color' => 'primary'])
    Ver Mis Solicitudes de Cometido
@endcomponent
@endif

Saludos cordiales,

{{ config('app.name') }}
@endcomponent
