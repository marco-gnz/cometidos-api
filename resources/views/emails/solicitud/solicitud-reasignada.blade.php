@component('mail::message')
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
@endcomponent
