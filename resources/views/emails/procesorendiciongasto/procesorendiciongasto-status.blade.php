@component('mail::message')
    # Actualización rendición de gasto

    Estimada/o {{ $last_status->procesoRendicionGasto->userBy->abreNombres() }}

    Con fecha {{ \Carbon\Carbon::parse($last_status->fecha_by_user)->format('d-m-Y H:i:s') }}, la rendición de gastos con N°
    de folio {{ $last_status->procesoRendicionGasto->n_folio }} ha sido
    {{ App\Models\EstadoProcesoRendicionGasto::STATUS_NOM[$last_status->status] }} por
    {{ $last_status->userBy->nombre_completo }}
    ({{ $last_status->perfil ? $last_status->perfil->name : '' }}).

    @if ($last_status->observacion)
        Observación: {{ $last_status->observacion }}
    @endif

    Saludos cordiales,
    {{ config('app.name') }}
@endcomponent
