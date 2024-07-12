{{-- @component('mail::message')
# Solicitud de cometido reasignada

Estimada/o {{ $last_status->funcionarioRs->abreNombres() }}

Con fecha {{ \Carbon\Carbon::parse($last_status->created_at)->format('d-m-Y H:i:s') }}, la solicitud de cometido con N°
resolución {{ $solicitud->codigo }} ha sido reasignada a tu firma por
{{ $last_status->funcionario->nombre_completo }}
({{ $last_status->perfil ? $last_status->perfil->name : '' }}).

@if ($last_status->observacion)
    Observación: {{ $last_status->observacion }}
@endif

Saludos cordiales,
{{ config('app.name') }}
@endcomponent --}}

<!-- resources/views/vendor/mail/html/message.blade.php -->
@component('mail::message')
# Solicitud de Cometido Reasignada

Estimado/a {{ $last_status->funcionarioRs->abreNombres()}},

La solicitud de cometido con el número de resolución <strong>{{ $solicitud->codigo }}</strong>, ha sido reasignada a tu firma. A continuación, encontrará un resumen de la solicitud:

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
        <tr>
            <th>Movimiento:</th>
            <td>{{ $last_status->perfil ? $last_status->perfil->name : '' }} - {{ $last_status->funcionario->nombre_completo }}</td>
        </tr>
        <tr>
            <th>Fecha Movimiento:</th>
            <td>{{ \Carbon\Carbon::parse($last_status->created_at)->format('d-m-Y H:i:s') }}</td>
        </tr>
        <tr>
            <th>Observación:</th>
            <td>{{ $last_status->observacion ? $last_status->observacion : '' }}</td>
        </tr>
    </table>
@endslot
@endcomponent

@component('mail::button', ['url' => config('app.frontend_url'), 'color' => 'primary'])
    Ir a {{ config('app.name') }}
@endcomponent

Saludos cordiales,

{{ config('app.name') }}
@endcomponent

