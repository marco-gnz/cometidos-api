@component('mail::message')
    # Solicitud de cometido modificada

    Estimada/o {{ $solicitud->funcionario->abreNombres() }}

    Se confirma que su Solicitud de Cometido ha sido modificada correctamente.

    Además, la solicitud se ha modificado a {{$solicitud->derecho_pago ? 'con derecho' : 'sin derecho'}} a pago.

    N° de resolución de cometido: {{ $solicitud->codigo }}

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
