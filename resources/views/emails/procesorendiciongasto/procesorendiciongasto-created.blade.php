@component('mail::message')
    # Nueva rendición de gastos

    Estimada/o {{ $proceso_rendicion->userBy->abreNombres() }}

    Se confirma que su Rendición de Gastos ha sido ingresada correctamente.

    N° de resolución de Cometido: {{ $proceso_rendicion->solicitud->codigo }}
    N° de folio de Rendición de Gastos: {{ $proceso_rendicion->n_folio }}

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
