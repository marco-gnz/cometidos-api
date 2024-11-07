<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class Convenio extends Model
{
    protected $table        = "convenios";
    protected $primaryKey   = 'id';

    public const TYPE_COMETIDOS = 0;

    public const TYPE_NOM = [
        self::TYPE_COMETIDOS => 'COMETIDOS'
    ];

    protected $fillable = [
        'uuid',
        'codigo',
        'fecha_inicio',
        'fecha_termino',
        'fecha_resolucion',
        'n_resolucion',
        'n_viatico_mensual',
        'tipo_convenio',
        'tipo_contrato',
        'anio',
        'observacion',
        'active',
        'email',
        'user_id',
        'estamento_id',
        'ley_id',
        'establecimiento_id',
        'ilustre_id',
        'user_id_by',
        'user_id_update',
    ];

    protected static function booted()
    {
        static::creating(function ($convenio) {
            $convenio->uuid                    = Str::uuid();
            $convenio->user_id_by              = Auth::check() ? Auth::user()->id : null;
        });

        static::created(function ($convenio) {
            $convenio->codigo               = self::generarCodigo($convenio);
            $convenio->tipo_convenio        = self::TYPE_COMETIDOS;
            $convenio->save();
        });
    }

    private static function generarCodigo($convenio)
    {
        $correlativo            = str_pad(self::whereYear('created_at', $convenio->created_at->year)->count(), 5, '0', STR_PAD_LEFT);
        $anio                   = $convenio->created_at->year;
        $codigo                 = "{$correlativo}/{$anio}";
        return $codigo;
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }

    public function estamento()
    {
        return $this->belongsTo(Estamento::class, 'estamento_id');
    }

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function ilustre()
    {
        return $this->belongsTo(Ilustre::class, 'ilustre_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }

    public function scopeInput($query, $params)
    {
        if ($params)
            return $query->where('codigo', 'like', '%' . $params . '%')
                ->orWhere('n_resolucion', 'like', '%' . $params . '%')
                ->orWhere('email', 'like', '%' . $params . '%')
                ->orWhere('tipo_contrato', 'like', '%' . $params . '%')
                ->orWhere('observacion', 'like', '%' . $params . '%')
                ->orWhere(function ($query) use ($params) {
                    $query->whereHas('funcionario', function ($query) use ($params) {
                        $query->where('rut_completo', 'like', '%' . $params . '%')
                            ->orWhere('rut', 'like', '%' . $params . '%')
                            ->orWhere('nombres', 'like', '%' . $params . '%')
                            ->orWhere('apellidos', 'like', '%' . $params . '%')
                            ->orWhere('nombre_completo', 'like', '%' . $params . '%')
                            ->orWhere('email', 'like', '%' . $params . '%');
                    });
                });
    }

    public function scopeEstablecimiento($query, $params)
    {
        if ($params)
            return $query->whereHas('establecimiento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopePeriodo($query, $params)
    {
        if ($params) {
            return $query->whereBetween('fecha_inicio', array($params[0], $params[1]));
        }
    }

    public function scopeLey($query, $params)
    {
        if ($params)
            return $query->whereHas('ley', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeIlustre($query, $params)
    {
        if ($params)
            return $query->whereHas('ilustre', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeStatus($query, $params)
    {
        if ($params)
            return $query->whereIn('active', $params);
    }

    public function authorizedToDelete()
    {
        return Gate::allows('delete', $this);
    }

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
    }

    public function solicitudesPorAnioYMes($anio = null)
    {
        $years = $this->yearsToSolicitudes();
        if(count($years) <= 0){
            return [];
        }
        setlocale(LC_ALL, "es_ES");
        Carbon::setLocale('es');
        $anio = $anio ? $anio : Carbon::now()->format('Y');
        $solicitudes = $this->solicitudes()
            ->whereYear('fecha_inicio', $anio)
            ->orderBy('fecha_inicio', 'ASC')
            ->get()
            ->groupBy(function ($solicitud) {
                return Carbon::parse($solicitud->fecha_inicio)->locale('es')->format('F');
            });

        $resultado = collect();

        foreach (range(1, 12) as $mes) {
            $nombreMes_spanish  = Carbon::createFromDate($anio, $mes, 1)->locale('es')->monthName;
            $nombreMes          = Carbon::createFromDate($anio, $mes, 1)->locale('es')->format('F');

            $solicitudesMes = $solicitudes->get($nombreMes, collect());
            $resultado->push([
                'year_month'    => "{$nombreMes_spanish}",
                'n_solicitudes' => $solicitudesMes->count(),
                'solicitudes'   => $solicitudesMes->map(function ($solicitud) {
                    return [
                        'codigo'                => $solicitud->codigo,
                        'fecha_inicio'          => Carbon::parse($solicitud->fecha_inicio)->format('d-m-y'),
                        'fecha_termino'         => Carbon::parse($solicitud->fecha_termino)->format('d-m-y'),
                        'derecho_pago_value'    => $solicitud->derecho_pago ? true : false,
                        'derecho_pago'          => $solicitud->derecho_pago ? "Si" : "No",
                        'type'                  => $solicitud->typeStatus(),
                        'page_firma'            => $solicitud->pageFirma(),
                        'type_page_firma'       => $solicitud->typePageFirma(),
                        'estado_nom'            => Solicitud::STATUS_NOM[$solicitud->status],
                        'tipo'                  => $solicitud->tipoComision->nombre
                    ];
                })->values()
            ]);
        }

        return $resultado;
    }

    public function yearsToSolicitudes()
    {
        $years = $this->solicitudes()
            ->selectRaw('YEAR(fecha_inicio) as year')
            ->orderBy('year', 'ASC')
            ->distinct()
            ->pluck('year');

        return $years;
    }

    public function yearToFirstSelected($year = null)
    {
        if($year){
            return $year;
        }
        $years = $this->yearsToSolicitudes();

        if (count($years) <= 0) {
            return null;
        }

        $currentYear = Carbon::now()->format('Y');
        if ($years->contains($currentYear)) {
            return $currentYear;
        }
        return $years->last();
    }
}
