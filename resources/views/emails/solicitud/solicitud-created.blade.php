<!-- resources/views/vendor/mail/html/message.blade.php -->
@component('mail::message')
# Nueva Solicitud de Cometido

Estimado/a {{ $solicitud->funcionario->nombres}} {{ $solicitud->funcionario->apellidos}},

Se confirma que la solicitud de cometido con el número de resolución <strong>{{ $solicitud->codigo }}</strong>, ha sido ingresada correctamente. A continuación, encontrará un resumen de su solicitud:

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
    </table>
@endslot
@endcomponent

@component('mail::button', ['url' => config('app.frontend_url') . '/mi-cuenta/solicitudes', 'color' => 'primary'])
    Ver Mis Solicitudes de Cometido
@endcomponent

Saludos cordiales,

{{ config('app.name') }}
@endcomponent
