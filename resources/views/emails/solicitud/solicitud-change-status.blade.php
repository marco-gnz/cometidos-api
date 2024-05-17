@component('mail::message')
    # Actualización solicitud de cometido

    Estimada/o {{ $solicitud->funcionario->abreNombres() }}

    Con fecha {{ \Carbon\Carbon::parse($last_status->created_at)->format('d-m-Y H:i:s') }}, la solicitud de cometido N°
    resolución {{ $solicitud->codigo }} ha sido {{ App\Models\EstadoSolicitud::STATUS_NOM[$last_status->status] }} por
    {{ $last_status->funcionario->nombre_completo }}
    ({{ $last_status->perfil ? $last_status->perfil->name : '' }}).

    @if (App\Models\EstadoSolicitud::STATUS_RECHAZADO === $last_status->status)
        Motivo: {{ App\Models\EstadoSolicitud::RECHAZO_NOM[$last_status->motivo_rechazo] }}
    @endif

    @if ($last_status->observacion)
        Observación: {{ $last_status->observacion }}
    @endif

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
