@component('mail::message')
    # Solicitud de cometido modificada

    Estimada/o {{ $solicitud->funcionario->abreNombres() }}

    Se confirma que su Solicitud de Cometido ha sido modificada correctamente.

    Además, se ha modificado el Derecho a Pago.

    N° de resolución de Cometido: {{ $solicitud->codigo }}

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
