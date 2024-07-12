@component('mail::message')
# Nuevo Proceso de Rendición de Gastos

Estimado/a {{ $proceso_rendicion->userBy->nombres}} {{ $proceso_rendicion->userBy->apellidos}},

Se confirma que el proceso de rendición de gastos con el número de folio <strong>{{ $proceso_rendicion->n_folio }}</strong>, ha sido ingresado correctamente. A continuación, encontrará un resumen del proceso de rendición de gastos:

@component('vendor.mail.html.panel')

@slot('content')
    <table class="table">
        <tr>
            <th>N° Resolución Cometido</th>
            <td>{{$proceso_rendicion->solicitud->codigo}}</td>
        </tr>
        <tr>
            <th>Items Rendidos</th>
            <td>{{$proceso_rendicion->nomRendicionesSolicitadas()}}</td>
        </tr>
        <tr>
            <th>Monto Total Rendido</th>
            <td>{{$proceso_rendicion->sumRendicionesSolicitadas()}}</td>
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
