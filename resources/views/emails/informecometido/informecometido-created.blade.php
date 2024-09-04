@component('mail::message')
# Nuevo Informe de Cometido

Estimado/a {{ $informe_cometido->userBy->nombres}} {{ $informe_cometido->userBy->apellidos}},

Se confirma que el Informe de Cometido en la solicitud con número de resolución <strong>{{ $informe_cometido->solicitud->codigo }}</strong>, ha sido ingresado correctamente. A continuación, encontrará un resumen de su informe de cometido:

@component('vendor.mail.html.panel')

@slot('content')
    <table class="table">
        <tr>
            <th>Periodo del Cometido:</th>
            <td>{{ \Carbon\Carbon::parse($informe_cometido->fecha_inicio)->format('d-m-Y') }} / {{ \Carbon\Carbon::parse($informe_cometido->fecha_termino)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <th>Hora de Salida / Llegada:</th>
            <td>{{ \Carbon\Carbon::parse($informe_cometido->hora_llegada)->format('H:i') }} / {{ \Carbon\Carbon::parse($informe_cometido->hora_salida)->format('H:i') }} hrs.</td>
        </tr>
        <tr>
            <th>Utiliza Transporte:</th>
            <td>{{ $informe_cometido->utiliza_transporte ? 'Sí' : 'No' }}</td>
        </tr>
        @if ($informe_cometido->utiliza_transporte)
            <tr>
                <th>Medio de Transporte:</th>
                <td>{{ $informe_cometido->transportes ? $informe_cometido->transportes->pluck('nombre')->implode(', ') : '' }}</td>
            </tr>
        @endif
        <tr>
            <th>Actividad Realizada:</th>
            <td>{{ $informe_cometido->actividad_realizada}}</td>
        </tr>
    </table>
@endslot
@endcomponent

El informe de cometido deberá ser firmado por su jefatura directa, quien ha sido copiado en este mismo correo.

@component('mail::button', ['url' => config('app.frontend_url') . '/mi-cuenta/solicitudes', 'color' => 'primary'])
    Ver Mis Solicitudes de Cometido
@endcomponent

Saludos cordiales,

{{ config('app.name') }}
@endcomponent
