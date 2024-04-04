<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProcesoRendicionGasto extends Model
{
    protected $table        = "proceso_rendicion_gastos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'n_rendicion',
        'n_folio',
        'fecha_pago',
        'status',
        'observacion',
        'solicitud_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    protected static function booted()
    {
        static::creating(function ($proceso) {
            $codigo_solicitud                 = $proceso->solicitud->codigo;
            $n_rendicion                      = self::where('solicitud_id', $proceso->solicitud->id)->count() + 1;
            $folio                            = "{$n_rendicion}{$codigo_solicitud}";
            $proceso->n_rendicion             = $n_rendicion;
            $proceso->n_folio                 = $folio;
            $proceso->uuid                    = Str::uuid();
            $proceso->user_id_by              = Auth::user()->id;
            $proceso->fecha_by_user           = now();
        });

        static::deleting(function ($proceso) {
            $proceso->documentos()->each(function ($documento) {
                $documento->delete();
            });
        });
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function rendiciones()
    {
        return $this->hasMany(RendicionGasto::class)->orderBy('rinde_gasto', 'DESC');
    }

    public function addRendiciones(array $data)
    {
        return $this->rendiciones()->createMany($data);
    }

    public function addEstados(array $data)
    {
        return $this->estados()->createMany($data);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    public function estados()
    {
        return $this->hasMany(EstadoProcesoRendicionGasto::class, 'p_rendicion_gasto_id')->orderBy('id', 'DESC');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }

    public function rendicionesfinanzas()
    {
        return $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->where('last_status', EstadoRendicionGasto::STATUS_APROBADO)
            ->orderBy('mount_real', 'DESC')
            ->get();
    }

    public function sumRendicionesSolicitadas()
    {
        $total = $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->sum('mount');
        return self::formatTotal($total);
    }

    public function sumRendicionesAprobadasValue()
    {
        $total = $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->where('last_status', EstadoRendicionGasto::STATUS_APROBADO)
            ->sum('mount_real');
        return $total;
    }

    public function sumRendicionesAprobadas()
    {
        $total = $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->where('last_status', EstadoRendicionGasto::STATUS_APROBADO)
            ->sum('mount_real');
        return self::formatTotal($total);
    }

    public function totalRendicionesSolicitadas()
    {
        $total = $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->count();
        return $total;
    }

    public function totalRendicionesAprobadas()
    {
        $total = $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->where('last_status', EstadoRendicionGasto::STATUS_APROBADO)
            ->count();
        return $total;
    }

    public function sumRendiciones($is_particular, $rinde_gasto, $status, $type_mount)
    {
        if ($is_particular === null || $rinde_gasto === null || $status === null || $type_mount === null) {
            return self::formatTotal(0);
        }

        $total = $this->hasMany(RendicionGasto::class)
            ->whereHas('actividad', function ($q) use ($is_particular) {
                $q->where('is_particular', $is_particular);
            })
            ->where('rinde_gasto', $rinde_gasto)
            ->whereIn('last_status', $status)
            ->sum($type_mount);
        return self::formatTotal($total);
    }

    private function formatTotal($total)
    {
        return "$" . number_format($total, 0, ',', '.');
    }

    public function isRendicionesModificadas()
    {
        $total_modificaciones = $this->rendiciones()
            ->where(function ($q) {
                $q->where('last_status', EstadoRendicionGasto::STATUS_RECHAZADO)
                    ->orWhereColumn('mount', '!=', 'mount_real');
            })
            ->count();

        if ($total_modificaciones > 0) {
            return true;
        }
        return false;
    }

    public function typeStatus($status)
    {
        switch ($status) {
            case 0:
            case 1:
                $type = 'info';
                break;

            case 2:
            case 4:
                $type = 'primary';
                break;

            case 3:
                $type = 'warning';
                break;

            case 5:
            case 6:
                $type = 'success';
                break;

            case 7:
                $type = 'danger';
                break;
        }
        return $type;
    }

    public function authorizedToDelete()
    {
        return Gate::allows('delete', $this);
    }

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
    }

    public function authorizedToUpdateFechaPago()
    {
        return Gate::allows('updatefechapago', $this);
    }

    public function authorizedToAnular()
    {
        return Gate::allows('anular', $this);
    }

    public function authorizedToAprobar()
    {
        return Gate::allows('aprobar', $this);
    }

    public function exportarDocumentos()
    {
        $documentos = [
            [
                'name'          => 'Gastos de Cometido Funcional',
                'url'           => route('gastoscometidofuncional.show', ['uuid' => $this->uuid]),
                'disabled'      => $this->status === EstadoProcesoRendicionGasto::STATUS_APROBADO_N || $this->status === EstadoProcesoRendicionGasto::STATUS_APROBADO_S ? false : true
            ]
        ];

        return $documentos;
    }

    public function firmaJefePersonal()
    {
        $last_status_aprobado = $this->estados()->where('status', EstadoProcesoRendicionGasto::STATUS_APROBADO_JP)->orderBy('id', 'DESC')->first();
        if ($last_status_aprobado) {
            $nombres    = $last_status_aprobado->userBy->abreNombres();
            $fecha      = Carbon::parse($last_status_aprobado->created_at)->format('d-m-y H:i:s');
            $new_firma  = "$nombres $fecha";
            return $new_firma;
        }
        return null;
    }
}
