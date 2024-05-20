@component('mail::message')
    # Solicitud de modificación de datos

    Estimada/o {{ $history->userSendBy->abreNombres() }}

    Con fecha {{ \Carbon\Carbon::parse($history->created_at)->format('d-m-Y H:i:s') }}, {{$history->userBy->nombre_completo}} ({{$history->userBy->rut_completo}})
    ha solicitado la modificación de sus datos de cuenta.

    @if ($history->observacion)
        Observación: {{ $history->observacion }}
    @endif

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
