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
        'dias_habiles_pago',
        'fecha_pago',
        'fecha_last_firma',
        'status',
        'posicion_firma_actual',
        'posicion_firma_ok',
        'observacion',
        'solicitud_id',
        'cuenta_bancaria_id',
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
            $proceso->fecha_last_firma        = now();
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

    public function cuentaBancaria()
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_bancaria_id');
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

    public function nomRendicionesSolicitadas()
    {
        return $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->with('actividad')
            ->get()
            ->pluck('actividad.nombre')
            ->implode(', ');
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
            case 8:
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

    public function authorizedToUpdatePago()
    {
        return Gate::allows('updatepago', $this);
    }

    public function authorizedToAnular()
    {
        return Gate::allows('anular', $this);
    }

    public function authorizedToAprobar()
    {
        return Gate::allows('aprobar', $this);
    }

    public function authorizedToRechazar()
    {
        return Gate::allows('rechazar', $this);
    }

    public function pagoHabilesMessage()
    {
        $dias_habiles_pago_message = null;
        if ($this->dias_habiles_pago !== null) {
            $fecha_pago = Carbon::parse($this->fecha_pago)->format('d-m-Y');
            $message_dias = $this->dias_habiles_pago > 1 ? 'días hábiles' : 'día hábil';
            $dias_habiles_pago_message = "El pago se realizará dentro de {$this->dias_habiles_pago} {$message_dias} de ser aprobado por Depto Finanzas. (Hasta el: $fecha_pago)";
        }
        return $dias_habiles_pago_message;
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

    public function firmaJefeDirecto()
    {
        $last_status_aprobado = $this->estados()->where('status', EstadoProcesoRendicionGasto::STATUS_APROBADO_JD)->orderBy('id', 'DESC')->first();
        if ($last_status_aprobado) {
            $nombres    = $last_status_aprobado->userBy->abreNombres();
            $fecha      = Carbon::parse($last_status_aprobado->fecha_by_user)->format('d-m-Y H:i:s');
            $new_firma  = "$nombres $fecha";
            return $new_firma;
        }
        return null;
    }

    public function firmaSupervisorFinanzas()
    {
        $last_status_aprobado = $this->estados()->whereIn('status', [EstadoProcesoRendicionGasto::STATUS_APROBADO_N, EstadoProcesoRendicionGasto::STATUS_APROBADO_S])->orderBy('id', 'DESC')->first();
        if ($last_status_aprobado) {
            $nombres    = $last_status_aprobado->userBy->abreNombres();
            $fecha      = Carbon::parse($last_status_aprobado->fecha_by_user)->format('d-m-Y H:i:s');
            $new_firma  = "$nombres $fecha";
            return $new_firma;
        }
        return null;
    }

    public function scopeSearchInput($query, $params)
    {
        if ($params)
            return $query->where('n_folio', 'like', '%' . $params . '%')
                ->orWhere('observacion', 'like', '%' . $params . '%')
                ->orWhere('n_rendicion', 'like', '%' . $params . '%')
                ->orWhere(function ($query) use ($params) {
                    $query->whereHas('solicitud', function ($query) use ($params) {
                        $query->where('codigo', 'like', '%' . $params . '%')
                            ->orWhere('actividad_realizada', 'like', '%' . $params . '%')
                            ->orWhere('vistos', 'like', '%' . $params . '%')
                            ->orWhere('observacion_gastos', 'like', '%' . $params . '%');
                    });
                })
                ->orWhere(function ($query) use ($params) {
                    $query->whereHas('solicitud.funcionario', function ($query) use ($params) {
                        $query->where('rut_completo', 'like', '%' . $params . '%')
                            ->orWhere('rut', 'like', '%' . $params . '%')
                            ->orWhere('nombres', 'like', '%' . $params . '%')
                            ->orWhere('apellidos', 'like', '%' . $params . '%')
                            ->orWhere('nombre_completo', 'like', '%' . $params . '%')
                            ->orWhere('email', 'like', '%' . $params . '%');
                    });
                })->orWhere(function ($query) use ($params) {
                    $query->whereHas('solicitud.firmantes.funcionario', function ($query) use ($params) {
                        $query->where('rut_completo', 'like', '%' . $params . '%')
                            ->orWhere('rut', 'like', '%' . $params . '%')
                            ->orWhere('nombres', 'like', '%' . $params . '%')
                            ->orWhere('apellidos', 'like', '%' . $params . '%')
                            ->orWhere('nombre_completo', 'like', '%' . $params . '%')
                            ->orWhere('email', 'like', '%' . $params . '%');
                    });
                })->orWhere(function ($query) use ($params) {
                    $query->whereHas('estados', function ($query) use ($params) {
                        $query->where('observacion', 'like', '%' . $params . '%');
                    });
                });
    }

    public function scopePeriodoSolicitud($query, $params)
    {
        if ($params) {
            return $query->whereHas('solicitud', function ($q) use ($params) {
                $q->whereBetween('fecha_inicio', array($params[0], $params[1]));
            });
        }
    }

    public function scopePeriodoPagoRendicion($query, $params)
    {
        if ($params) {
            return $query;
        }
    }

    public function scopePeriodoIngresoSolicitud($query, $params)
    {
        if ($params) {
            return $query->whereHas('solicitud', function ($q) use ($params) {
                $inicio     = Carbon::parse($params[0])->startOfDay();
                $termino    = Carbon::parse($params[1])->endOfDay();
                $q->whereBetween('fecha_by_user', array($inicio, $termino));
            });
        }
    }

    public function scopePeriodoIngresoProceso($query, $params)
    {
        if ($params) {
            $inicio     = Carbon::parse($params[0])->startOfDay();
            $termino    = Carbon::parse($params[1])->endOfDay();

            return $query->whereBetween('fecha_by_user', array($inicio, $termino));
        }
    }

    public function scopeDerechoViatico($query, $params)
    {
        if ($params) {
            return $query->whereHas('solicitud', function ($q) use ($params) {
                $q->whereIn('derecho_pago', $params);
            });
        }
    }

    public function scopeArchivos($query, $params)
    {
        if ($params) {
            if (in_array(0, $params) && in_array(1, $params)) {
                return $query;
            } elseif (in_array(0, $params)) {
                return $query->whereDoesntHave('documentos');
            } elseif (in_array(1, $params)) {
                return $query->whereHas('documentos');
            }
        }
    }

    public function scopeMotivo($query, $params)
    {
        if ($params)
            return $query->whereHas('solicitud.motivos', function ($q) use ($params) {
                $q->whereIn('motivos.id', $params);
            });
    }

    public function scopeLugar($query, $params)
    {
        if ($params)
            return $query->whereHas('solicitud.lugares', function ($q) use ($params) {
                $q->whereIn('lugars.id', $params);
            });
    }

    public function scopePais($query, $params)
    {
        if ($params)
            return $query->whereHas('solicitud.paises', function ($q) use ($params) {
                $q->whereIn('countries.id', $params);
            });
    }

    public function scopeTipoComision($query, $params)
    {
        if ($params)
            return $query->whereHas('solicitud.tipoComision', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeJornada($query, $params)
    {
        if ($params)
            return $query->whereHas('solicitud', function ($q) use ($params) {
                $q->whereIn('jornada', $params);
            });
    }

    public function scopeEstado($query, $params)
    {
        if ($params)
            return $query->whereHas('solicitud', function ($q) use ($params) {
                $q->whereIn('status', $params);
            });
    }

    public function scopeConcepto($query, $params)
    {
        if ($params)
            return $query->whereHas('rendiciones.actividad', function ($q) use ($params) {
                $q->whereIn('id', $params)
                    ->where('rinde_gasto', true);
            });
    }

    public function scopeEstadoRendicion($query, $params)
    {
        if ($params)
            return $query->whereIn('proceso_rendicion_gastos.status', $params);
    }
}
