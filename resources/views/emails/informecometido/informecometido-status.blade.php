@component('mail::message')
    # Actualización informe de cometido

    Estimada/o {{ $last_status->informeCometido->userBy->abreNombres() }}

    Con fecha {{ \Carbon\Carbon::parse($last_status->fecha_by_user)->format('d-m-Y H:i:s') }}, el informe de cometido con
    código {{ $last_status->informeCometido->codigo }} ha sido
    {{ App\Models\EstadoInformeCometido::STATUS_NOM[$last_status->status] }} por {{ $last_status->userBy->nombre_completo }}
    ({{ $last_status->perfil ? $last_status->perfil->name : '' }}).

    @if ($last_status->observacion)
        Observación: {{ $last_status->observacion }}
    @endif

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
