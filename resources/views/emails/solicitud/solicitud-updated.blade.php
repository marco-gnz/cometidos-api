@component('mail::message')
# Solicitud de Cometido Modificada

Estimada/o {{ $solicitud->funcionario->nombres }} {{ $solicitud->funcionario->apellidos }},

Se confirma que la solicitud de cometido con el número de resolución <strong>{{ $solicitud->codigo }}</strong>, ha sido modificada correctamente.
Además, la solicitud se ha modificado a <strong>{{$solicitud->derecho_pago ? 'con derecho' : 'sin derecho'}} a pago</strong>.

@if ($solicitud->informeCometido())
Al modificar la solicitud del cometido, por favor revise su Informe de Cometido, con el fin de corregir los datos si es necesario.
@endif

@component('mail::button', ['url' => config('app.frontend_url') . '/mi-cuenta/solicitudes', 'color' => 'primary'])
    Ver Mis Solicitudes de Cometido
@endcomponent

Saludos cordiales,

{{ config('app.name') }}
@endcomponent
