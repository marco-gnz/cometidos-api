@component('mail::message')
    # Nueva solicitud de cometido

    Estimada/o {{ $solicitud->funcionario->abreNombres() }}

    Se confirma que su Solicitud de Cometido ha sido ingresada correctamente.

    N° de resolución de Cometido: {{ $solicitud->codigo }}

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
