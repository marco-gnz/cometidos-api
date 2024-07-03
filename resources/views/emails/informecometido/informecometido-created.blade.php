@component('mail::message')
    # Nuevo informe de cometido

    Estimada/o {{ $informe_cometido->userBy->abreNombres() }}

    Se confirma que su Informe de Cometido ha sido ingresado correctamente.

    Código Informe de Cometido: {{ $informe_cometido->codigo }}
    N° resolución cometido: {{ $informe_cometido->solicitud->codigo }}

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
