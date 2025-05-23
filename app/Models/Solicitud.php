<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use App\Policies\InformeCometidoPolicy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Solicitud extends Model
{
    protected $table        = "solicituds";
    protected $primaryKey   = 'id';

    public const RESOLUCION_AFECTA      = 0;
    public const RESOLUCION_DECRETO     = 1;
    public const RESOLUCION_EXENTA      = 2;
    public const RESOLUCION_PROYECTO    = 3;
    public const RESOLUCION_REGISTRO    = 4;

    public const RESOLUCION_NOM = [
        self::RESOLUCION_AFECTA         => 'AFECTA',
        self::RESOLUCION_DECRETO        => 'DECRETO',
        self::RESOLUCION_EXENTA         => 'EXENTA',
        self::RESOLUCION_PROYECTO       => 'PROYECTO',
        self::RESOLUCION_REGISTRO       => 'REGISTRO'
    ];

    public const JORNADA_MANANA         = 0;
    public const JORNADA_TARDE          = 1;
    public const JORNADA_NOCHE          = 2;
    public const JORNADA_TODO_EL_DIA    = 3;

    public const JORNADA_NOM = [
        self::JORNADA_MANANA        => 'Mañana',
        self::JORNADA_TARDE         => 'Tarde',
        self::JORNADA_NOCHE         => 'Noche',
        self::JORNADA_TODO_EL_DIA   => 'Todo el día'
    ];

    public const JORNADA_ABRE = [
        self::JORNADA_MANANA        => 'AM',
        self::JORNADA_TARDE         => 'PM',
        self::JORNADA_NOCHE         => 'NO',
        self::JORNADA_TODO_EL_DIA   => 'TD'
    ];

    public const JORNADA_COMETIDOS = [
        ['id' => self::JORNADA_MANANA, 'nombre' => self::JORNADA_NOM[self::JORNADA_MANANA]],
        ['id' => self::JORNADA_TARDE, 'nombre' => self::JORNADA_NOM[self::JORNADA_TARDE]],
        ['id' => self::JORNADA_NOCHE, 'nombre' => self::JORNADA_NOM[self::JORNADA_NOCHE]],
        ['id' => self::JORNADA_TODO_EL_DIA, 'nombre' => self::JORNADA_NOM[self::JORNADA_TODO_EL_DIA]],
    ];

    public const STATUS_EN_PROCESO      = 0;
    public const STATUS_PROCESADO       = 1;
    public const STATUS_ANULADO         = 2;


    public const STATUS_NOM = [
        self::STATUS_EN_PROCESO         => 'EN PROCESO',
        self::STATUS_PROCESADO          => 'PROCESADA',
        self::STATUS_ANULADO            => 'ANULADA',
    ];

    public const STATUS_DESC = [
        self::STATUS_EN_PROCESO         => 'Solicitud de cometido no verficiada por los firmantes.',
        self::STATUS_PROCESADO          => 'Solicitud de cometido verficiada por todos los firmantes',
        self::STATUS_ANULADO            => 'Solicitud de cometido anulada',
    ];

    public const STATUS_COMETIDO = [
        ['id' => self::STATUS_EN_PROCESO, 'nombre' => self::STATUS_NOM[self::STATUS_EN_PROCESO]],
        ['id' => self::STATUS_PROCESADO, 'nombre' => self::STATUS_NOM[self::STATUS_PROCESADO]],
        ['id' => self::STATUS_ANULADO, 'nombre' => self::STATUS_NOM[self::STATUS_ANULADO]],
    ];

    protected $fillable = [
        'uuid',
        'codigo',
        'correlativo',
        'fecha_inicio',
        'fecha_termino',
        'hora_llegada',
        'hora_salida',
        'derecho_pago',
        'utiliza_transporte',
        'viaja_acompaniante',
        'afecta_convenio',
        'actividad_realizada',
        'alimentacion_red',
        'gastos_alimentacion',
        'gastos_alojamiento',
        'pernocta_lugar_residencia',
        'n_dias_40',
        'n_dias_100',
        'observacion',
        'observacion_gastos',
        'status',
        'last_status',
        'tipo_resolucion',
        'jornada',
        'posicion_firma_actual',
        'dentro_pais',
        'total_dias_cometido',
        'total_horas_cometido',
        'valor_cometido_diario',
        'valor_cometido_parcial',
        'valor_pasaje',
        'valor_total',
        'vistos',
        'total_firmas',
        'load_sirh',
        'user_id',
        'grupo_id',
        'convenio_id',
        'departamento_id',
        'sub_departamento_id',
        'dias_permitidos',
        'ley_id',
        'calidad_id',
        'grado_id',
        'estamento_id',
        'establecimiento_id',
        'calculo_aplicado',
        'tipo_comision_id',
        'cargo_id',
        'hora_id',
        'cuenta_bancaria_id',
        'item_presupuestario_id',
        'fecha_last_firma',
        'posicion_firma_ok',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
        'is_reasignada',
        'n_contacto',
        'email',
        'nacionalidad_id'
    ];

    public function getItemPresupuestario($solicitud)
    {
        $numero_item = null;

        if ($solicitud->calidad_id !== 2) {
            $item = ItemPresupuestarioUser::where('calidad_id', $solicitud->calidad_id)
                ->where('ley_id', $solicitud->ley_id)
                ->first();
        } else {
            $item = ItemPresupuestarioUser::where('calidad_id', $solicitud->calidad_id)
                ->first();
        }

        if ($item) {
            $numero_item = $item->itemNumero;
        }
        return $numero_item;
    }

    protected static function booted()
    {
        static::creating(function ($solicitud) {
            $fecha_inicio                       = Carbon::parse($solicitud->fecha_inicio);
            $fecha_termino                      = Carbon::parse($solicitud->fecha_termino);
            $total_dias                         = $fecha_inicio->diffInDays($fecha_termino) + 1;
            $hora_llegada                       = Carbon::parse($solicitud->hora_llegada)->format('H:i:s');
            $hora_salida                        = Carbon::parse($solicitud->hora_salida)->format('H:i:s');

            $ini_date_time                      = $fecha_inicio->format('Y-m-d') . ' ' . $hora_llegada;
            $ter_date_time                      = $fecha_termino->format('Y-m-d') . ' ' . $hora_salida;
            $ini_date_time                      = Carbon::parse($ini_date_time);
            $ter_date_time                      = Carbon::parse($ter_date_time);
            $total_horas_cometido               = $ini_date_time->diffInHours($ter_date_time);

            $solicitud->uuid                    = Str::uuid();

            $solicitud->total_dias_cometido     = $total_dias;
            $solicitud->total_horas_cometido    = $total_horas_cometido;
            $solicitud->user_id                 = Auth::user()->id;
            $solicitud->user_id_by              = Auth::user()->id;
            $solicitud->fecha_by_user           = now();
            $solicitud->nacionalidad_id         = $solicitud->funcionario->nacionalidad ? $solicitud->funcionario->nacionalidad->id : null;
        });

        static::created(function ($solicitud) {
            $convenio_id = self::searchConvenio($solicitud);
            $dias_permitidos                = (int)Configuration::obtenerValor('informecometido.dias_atraso', $solicitud->establecimiento_id);
            $vistos                         = Configuration::obtenerValor('info.vistos', $solicitud->establecimiento_id);
            $item                           = self::getItemPresupuestario($solicitud);
            $solicitud->correlativo         = self::generarCorrelativo($solicitud);
            $solicitud->codigo              = self::generarCodigo($solicitud);
            $solicitud->item_presupuestario_id = $item ? $item->id : NULL;
            $solicitud->tipo_resolucion     = self::RESOLUCION_EXENTA;
            $solicitud->total_firmas        = $solicitud->firmantes()->where('status', true)->count();
            $solicitud->dias_permitidos     = $dias_permitidos;
            $solicitud->vistos              = $vistos;
            $solicitud->convenio_id         = $convenio_id;
            $solicitud->afecta_convenio     = $convenio_id !== null ? true : false;
            $solicitud->save();
        });

        static::updating(function ($solicitud) {
            $fecha_inicio                       = Carbon::parse($solicitud->fecha_inicio);
            $fecha_termino                      = Carbon::parse($solicitud->fecha_termino);
            $total_dias                         = $fecha_inicio->diffInDays($fecha_termino) + 1;
            $hora_llegada                       = Carbon::parse($solicitud->hora_llegada)->format('H:i:s');
            $hora_salida                        = Carbon::parse($solicitud->hora_salida)->format('H:i:s');

            $ini_date_time                      = $fecha_inicio->format('Y-m-d') . ' ' . $hora_llegada;
            $ter_date_time                      = $fecha_termino->format('Y-m-d') . ' ' . $hora_salida;
            $ini_date_time                      = Carbon::parse($ini_date_time);
            $ter_date_time                      = Carbon::parse($ter_date_time);
            $total_horas_cometido               = $ini_date_time->diffInHours($ter_date_time);

            $solicitud->total_dias_cometido     = $total_dias;
            $solicitud->total_horas_cometido    = $total_horas_cometido;

            $solicitud->codigo              = self::generarCodigoUpdate($solicitud);
            $solicitud->total_firmas        = $solicitud->firmantes()->where('status', true)->count();
        });
    }

    private static function searchConvenio($solicitud)
    {
        $fecha_inicio   = $solicitud->fecha_inicio;
        $fecha_termino  = $solicitud->fecha_termino;

        $first_convenio = $solicitud->funcionario->convenios()
            ->where('active', true)
            ->where('establecimiento_id', $solicitud->establecimiento_id)
            ->where('ley_id', $solicitud->ley_id)
            ->where('estamento_id', $solicitud->estamento_id)
            ->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                    $query->where('fecha_inicio', '<=', $fecha_inicio)
                        ->where('fecha_termino', '>=', $fecha_inicio);
                })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                    $query->where('fecha_inicio', '<=', $fecha_termino)
                        ->where('fecha_termino', '>=', $fecha_termino);
                })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                    $query->where('fecha_inicio', '>=', $fecha_inicio)
                        ->where('fecha_termino', '<=', $fecha_termino);
                });
            })
            ->first();

        return $first_convenio ? $first_convenio->id : null;
    }

    private static function generarCodigo($solicitud)
    {
        $derecho_pago           = $solicitud->derecho_pago ? 1 : 2;
        $codigoEstablecimiento  = $solicitud->establecimiento->cod_sirh;
        $correlativo            = self::generarCorrelativo($solicitud);
        $codigo                 = "{$codigoEstablecimiento}{$derecho_pago}{$correlativo}";
        return $codigo;
    }

    private static function generarCorrelativo($solicitud)
    {
        $correlativo            = str_pad(self::whereYear('created_at', $solicitud->created_at->year)->count(), 5, '0', STR_PAD_LEFT);
        $anio                   = $solicitud->created_at->year;
        $codigo                 = "{$anio}{$correlativo}";
        return $codigo;
    }

    private static function generarCodigoUpdate($solicitud)
    {
        $derecho_pago           = $solicitud->derecho_pago ? 1 : 2;
        $codigoEstablecimiento  = $solicitud->establecimiento->cod_sirh;
        $correlativo            = $solicitud->correlativo;
        $codigo                 = "{$codigoEstablecimiento}{$derecho_pago}{$correlativo}";
        return $codigo;
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_pinned')
            ->withTimestamps();
    }

    // Verificar si la solicitud está fijada por un usuario específico
    public function isPinnedByUser(User $user)
    {
        return $this->users()->where('user_id', $user->id)->wherePivot('is_pinned', true)->exists();
    }

    public function motivos()
    {
        return $this->belongsToMany(Motivo::class);
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function tipoComision()
    {
        return $this->belongsTo(TipoComision::class, 'tipo_comision_id');
    }

    public function subdepartamento()
    {
        return $this->belongsTo(SubDepartamento::class, 'sub_departamento_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function convenio()
    {
        return $this->belongsTo(Convenio::class, 'convenio_id');
    }

    public function estamento()
    {
        return $this->belongsTo(Estamento::class, 'estamento_id');
    }

    public function lugares()
    {
        return $this->belongsToMany(Lugar::class);
    }

    public function calidad()
    {
        return $this->belongsTo(Calidad::class, 'calidad_id');
    }

    public function paises()
    {
        return $this->belongsToMany(Country::class, 'country_solicitud');
    }

    public function transportes()
    {
        return $this->belongsToMany(Transporte::class);
    }

    public function actividades()
    {
        return $this->belongsToMany(ActividadGasto::class)->withPivot('mount', 'status', 'status_admin', 'rinde_gastos_servicio');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class)->orderBy('id', 'DESC');
    }

    public function loadsSirh()
    {
        return $this->hasMany(SolicitudSirhLoad::class)->orderBy('id', 'DESC');
    }

    public function estados()
    {
        return $this->hasMany(EstadoSolicitud::class)->orderBy('id', 'DESC');
    }

    public function procesoRendicionGastos()
    {
        return $this->hasMany(ProcesoRendicionGasto::class);
    }

    public function calculos()
    {
        return $this->hasMany(SoliucitudCalculo::class)->latest();
    }

    public function firmantes()
    {
        return $this->hasMany(SolicitudFirmante::class);
    }

    public function reasignaciones()
    {
        return $this->belongsToMany(Reasignacion::class);
    }

    public function informes()
    {
        return $this->hasMany(InformeCometido::class);
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class, 'hora_id');
    }

    public function nacionalidad()
    {
        return $this->belongsTo(Nacionalidad::class, 'nacionalidad_id');
    }

    public function cuentaBancaria()
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_bancaria_id');
    }

    public function itemPresupuestario()
    {
        return $this->belongsTo(ItemPresupuestario::class, 'item_presupuestario_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }

    public function addLoads(array $loads)
    {
        return $this->loadsSirh()->createMany($loads);
    }

    public function addDocumentos(array $documentos)
    {
        return $this->documentos()->createMany($documentos);
    }

    public function addFirmantes(array $firmantes)
    {
        return $this->firmantes()->createMany($firmantes);
    }

    public function addEstados(array $estados)
    {
        return $this->estados()->createMany($estados);
    }

    public function addInformes(array $informes)
    {
        return $this->informes()->createMany($informes);
    }

    public function getLastCalculo()
    {
        return $this->hasOne(SoliucitudCalculo::class)->latest()->first();
    }

    public function isPosibleGrupos()
    {
        if (self::authorizedToSincronizarGrupo()) {
            $grupos = Grupo::whereHas('firmantes', function ($q) {
                $q->where('role_id', 2);
            })->whereHas('firmantes', function ($q) {
                $q->where('role_id', 7);
            })
                ->where(function ($q) {
                    $q->where('establecimiento_id', $this->establecimiento_id)
                        ->where('departamento_id', $this->departamento_id);
                })->orWhere(function ($q) {
                    $q->whereHas('contratos', function ($query) {
                        $query->where('establecimiento_id', $this->establecimiento_id)
                            ->where('user_id', $this->user_id);
                    });
                })
                ->orderByRaw('CAST(codigo AS UNSIGNED) ASC')
                ->get();

            $grupos->map(function ($grupo) {
                if ($grupo) {
                    $existe_en_su_mismo_grupo = $grupo->firmantes()->where('user_id', $this->user_id)->first();
                    $grupo->{'es_su_grupo'} =  $existe_en_su_mismo_grupo ? true : false;
                }
                return $grupo;
            });
            return $grupos;
        }
        return null;
    }

    public function totalProcesosRendiciones()
    {
        $total = 0;

        $procesos = $this->procesoRendicionGastos()
            ->whereIn('status', [EstadoProcesoRendicionGasto::STATUS_APROBADO_N, EstadoProcesoRendicionGasto::STATUS_APROBADO_S])
            ->count();

        return $procesos;
    }

    public function sumTotalProcesosRendiciones()
    {
        $total = 0;

        $procesos = $this->procesoRendicionGastos()
            ->whereIn('status', [EstadoProcesoRendicionGasto::STATUS_APROBADO_N, EstadoProcesoRendicionGasto::STATUS_APROBADO_S])
            ->get();

        if ($procesos) {
            foreach ($procesos as $proceso) {
                $total += $proceso->sumRendicionesAprobadasValue();
            }
        }
        return "$" . number_format($total, 0, ',', '.');
    }

    public function sumTotalProcesosRendicionesNumber()
    {
        $total = 0;

        $procesos = $this->procesoRendicionGastos()
            ->whereIn('status', [EstadoProcesoRendicionGasto::STATUS_APROBADO_N, EstadoProcesoRendicionGasto::STATUS_APROBADO_S])
            ->get();

        if ($procesos) {
            foreach ($procesos as $proceso) {
                $total += $proceso->sumRendicionesAprobadasValue();
            }
        }
        return $total;
    }

    public function informeCometido()
    {
        $status = [
            EstadoInformeCometido::STATUS_INGRESADA,
            EstadoInformeCometido::STATUS_MODIFICADO,
            EstadoInformeCometido::STATUS_APROBADO
        ];
        return $this->informes()->whereIn('last_status', $status)->orderBy('fecha_by_user', 'DESC')->first();
    }

    public function isInformeAtrasado()
    {

        $informe = self::informeCometido();
        if (!$informe) {
            $dias_permitidos                = $this->dias_permitidos;
            $fecha_termino_informe          = "{$this->fecha_termino} {$this->hora_termino}";
            $fecha_termino_informe          = Carbon::parse($fecha_termino_informe);
            $fecha_ingreso                  = Carbon::parse($this->fecha_by_user);
            $plazo                          = $fecha_termino_informe->addDays($dias_permitidos);
            if ($fecha_ingreso->lte($plazo)) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    public function nResolucionSirh()
    {
        $derecho_pago   = $this->derecho_pago ? 1 : 2;
        $n_res_last     = substr($this->codigo, -5);
        return "{$derecho_pago}{$n_res_last}";
    }

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
    }

    public function authorizedToUpdateAdmin()
    {
        return Gate::allows('updateadmin', $this);
    }

    public function authorizedToFirma()
    {
        return Gate::allows('firma', $this);
    }

    public function authorizedToAnular()
    {
        return Gate::allows('anular', $this);
    }

    public function authorizedToAnularAdmin()
    {
        return Gate::allows('anularAdmin', $this);
    }

    public function authorizedToReasignarEmergency()
    {
        return Gate::allows('reasignaremergency', $this);
    }

    public function authorizedToSincronizarGrupo()
    {
        return Gate::allows('sincronizargrupo', $this);
    }

    public function authorizedToCreateCalculo()
    {
        return Gate::allows('createcalculo', $this);
    }

    public function authorizedToCreateCalculoAjuste()
    {
        return Gate::allows('createcalculoajuste', $this);
    }

    public function authorizedToDeleteCalculoAjuste()
    {
        return Gate::allows('deletecalculoajuste', $this);
    }

    public function authorizedToCreateConvenio()
    {
        return Gate::allows('createconvenio', $this);
    }

    public function authorizedToVerDatos()
    {
        return Gate::allows('verdatos', $this);
    }

    public function authorizedToVerFirmantes()
    {
        return Gate::allows('verfirmantes', $this);
    }

    public function authorizedToVerValorizacion()
    {
        return Gate::allows('vervalorizacion', $this);
    }

    public function authorizedToVerConvenio()
    {
        return Gate::allows('verconvenio', $this);
    }

    public function authorizedToVerRendicion()
    {
        return Gate::allows('verrendicion', $this);
    }

    public function authorizedToVerArchivos()
    {
        return Gate::allows('verarchivos', $this);
    }

    public function authorizedToVerInformes()
    {
        return Gate::allows('verinformes', $this);
    }

    public function authorizedToVerHistorial()
    {
        return Gate::allows('verhistorial', $this);
    }

    public function authorizedToLoadSirh()
    {
        return Gate::allows('loadsirh', $this);
    }

    public function authorizedToCreateInformeCometido()
    {
        $policy = resolve(InformeCometidoPolicy::class);
        return $policy->create(auth()->user(), new InformeCometido, $this);
    }

    public function isLoadSirhInfo()
    {
        $data = (object) [
            'type'      => $this->load_sirh ? 'success' : 'danger',
            'message'   => $this->load_sirh ? 'Cargado en SIRH' : 'No cargado en SIRH'
        ];
        return $data;
    }

    public function lastMovLoadSirh()
    {
        return $this->loadsSirh()->first();
    }

    public function isStore()
    {
        $informe = self::informeCometido();
        if (!$informe) {
            $fecha_termino_solicitud = Carbon::parse($this->fecha_termino);
            $now                     = Carbon::now();
            if ($fecha_termino_solicitud->lte($now)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    public function jefaturaDirecta()
    {
        return $this->firmantes()->where('role_id', 3)->where('status', true)->first();
    }

    public function isFirmaPendiente()
    {
        if ($this->status !== self::STATUS_EN_PROCESO) {
            return null;
        }

        $query = $this->firmantes()
            ->where('status', true)
            ->where('is_executed', false);

        if ($this->is_reasignada) {
            $query->where('is_reasignado', true)
                ->where('posicion_firma', $this->posicion_firma_actual);
        } else {
            $query->where('posicion_firma', '>', $this->posicion_firma_actual);
        }
        return $query->orderBy('posicion_firma', 'ASC')->first();
    }


    public function totalFirmasAprobadas()
    {
        return $this->firmantes()->where('role_id', '!=', 1)->where('status', true)->where('is_executed', true)->where('is_success', true)->count();
    }

    private function totalFirmas()
    {
        $count_firmantes = $this->firmantes()->where('role_id', '!=', 1)->where('status', true)->count();
        return $count_firmantes;
    }

    public function pageFirma()
    {
        $count_firmantes = self::totalFirmas();
        $firmas_aprobadas = self::totalFirmasAprobadas();

        return "{$firmas_aprobadas} de {$count_firmantes}";
    }

    public function typePageFirma()
    {
        $count_firmantes    = self::totalFirmas();
        $firmas_aprobadas   = self::totalFirmasAprobadas();
        $type               = 'info';

        if ($firmas_aprobadas > 0 && $firmas_aprobadas < $count_firmantes) {
            $type = 'primary';
        } else if ($firmas_aprobadas === $count_firmantes) {
            $type = 'success';
        }
        return $type;
    }

    public function pageFirmaIsOk()
    {
        $count_firmantes = self::totalFirmas();
        $firmas_aprobadas = self::totalFirmasAprobadas();

        if ($firmas_aprobadas === $count_firmantes) {
            return true;
        }
        return false;
    }

    public function typeStatus()
    {
        switch ($this->status) {
            case 0:
                $type = 'info';
                break;

            case 1:
                $type = 'success';
                break;

            case 2:
                $type = 'danger';
                break;
        }
        return $type;
    }

    public function typeLastStatus()
    {
        switch ($this->last_status) {
            case 1:
                $type = 'primary';
                break;

            case 2:
                $type = 'success';
                break;

            case 3:
            case 4:
                $type = 'danger';
                break;

            default:
                $type = 'info';
                break;
        }
        return $type;
    }

    public function exportarDocumentos()
    {
        $informe = self::informeCometido();
        $is_procesada = $this->status === self::STATUS_PROCESADO ? true : false;
        $documentos = [
            [
                'name'          => 'Informe de cometido',
                'url'           => $informe ? route('informecometido.show', ['uuid' => $informe->uuid]) : null,
                'exist'         => self::isAnulada() ? false : ($informe ? true : false),
                'stauts_nom'    => $informe ? EstadoInformeCometido::STATUS_NOM[$informe->last_status] : '',
                'type'          => $informe ? EstadoInformeCometido::STATUS_TYPE[$informe->last_status] : '',
                'habilitado'    => ($informe) && ($informe->last_status === EstadoInformeCometido::STATUS_APROBADO && $is_procesada) ? true : false
            ],
            [
                'name'  => 'Resolución de cometido',
                'url'   => route('resolucioncometidofuncional.show', ['uuid' => $this->uuid]),
                'exist' => self::isAnulada() ? false : true,
                'type'  => 'primary',
                'habilitado'    => $is_procesada
            ]
        ];

        return $documentos;
    }

    public function isNotActividad()
    {
        $last_status = $this->estados()->orderBy('id', 'DESC')->first();

        $data = (object)[
            'is_not_actividad'  => false,
            'time'              => null
        ];

        if (($last_status) && ($last_status->posicion_firma === 0 && $last_status->is_reasignado)) {
            $dias_atraso_actividad  = (int)Configuration::obtenerValor('solicitud.dias_atraso_actividad', $this->establecimiento_id);
            $last_movimiento_solicitud_date     = Carbon::parse($last_status->created_at);
            $array_fechas_feriados              = $this->feriados($last_movimiento_solicitud_date);
            $fechaLimite                        = $this->calcularFechaLimite($last_movimiento_solicitud_date, $dias_atraso_actividad, $array_fechas_feriados);
            $fechaLimite = $fechaLimite->format('d-m-Y');
            $motivo_nom  = $last_status->motivo_rechazo !== null ? " (" . EstadoSolicitud::RECHAZO_NOM[$last_status->motivo_rechazo] . ")" : '';
            $data = (object)[
                'is_not_actividad'  => true,
                'title'             => 'Sin actividad',
                'message'           => "Este cometido se anulará el $fechaLimite por falta de actividad$motivo_nom. Haga clic en Editar solicitud si necesita hacer cambios."
            ];

            return $data;
        }
        return $data;
    }

    private function feriados($fecha)
    {
        $fecha      = Carbon::parse($fecha);
        $anio       = $fecha->format('Y');
        $cacheKey   = "feriados_{$anio}";
        $feriados   = Cache::get($cacheKey);
        if ($feriados !== null) {
            return $feriados;
        }

        try {
            $url        = "https://apis.digital.gob.cl/fl/feriados/{$anio}";
            $response   = Http::get($url);
            if ($response->successful()) {
                $apiResponse = $response->body();
                $feriados = json_decode($apiResponse, true, 512, JSON_UNESCAPED_UNICODE);

                if (is_array($feriados)) {
                    $fechas = collect($feriados)->pluck('fecha')->toArray();
                    Cache::put($cacheKey, $fechas, now()->addDays(31));
                    return $fechas;
                }
            }
            return [];
        } catch (\Exception $exception) {
            Log::error("Error al procesar la solicitud de feriados: {$exception->getMessage()}");
            $feriados = Cache::get($cacheKey);
            return $feriados !== null ? $feriados : [];
        }
    }

    private function calcularFechaLimite(Carbon $fechaInicio, $diasHabiles, array $feriados)
    {
        $fechaLimite = $fechaInicio->copy();

        $feriados = array_filter(array_map(function ($feriado) {
            $feriadoCarbon = Carbon::parse($feriado);
            return !$feriadoCarbon->isWeekend() ? $feriadoCarbon : null;
        }, $feriados));

        while ($diasHabiles > 0) {
            $fechaLimite->addDay();
            if (!$fechaLimite->isWeekend() && !in_array($fechaLimite->format('Y-m-d'), array_map(function ($feriado) {
                return $feriado->format('Y-m-d');
            }, $feriados))) {
                $diasHabiles--;
            }
        }

        return $fechaLimite;
    }

    public function valorTotal()
    {
        $total          = 0;
        $calculo        = self::getLastCalculo();
        if ($calculo) {
            $total = $calculo->valorizacionTotalAjusteMonto()->total_valorizacion;
            return $total;
        }
        $total = "$" . number_format($total, 0, ",", ".");
        return  $total;
    }

    public function lastEstadoAprobado()
    {
        $firma = $this->estados()->where('status', EstadoSolicitud::STATUS_APROBADO)->orderBy('id', 'DESC')->first();

        return $firma;
    }

    public function firmaJefatura()
    {
        $last_status_aprobado = self::lastEstadoAprobado();
        if ($last_status_aprobado) {
            $firma = $this->estados()->where('s_role_id', 3)->where('status', EstadoSolicitud::STATUS_APROBADO)->where('created_at', '<=', $last_status_aprobado->created_at)->orderBy('id', 'DESC')->first();
            if ($firma) {
                $nombres        = $firma->funcionario->abreNombres();
                $fecha          = Carbon::parse($firma->created_at)->format('d-m-y H:i:s');
                $is_subrogante  = $firma->is_subrogante ? "(S)" : "";
                $new_firma      = "$nombres $fecha $is_subrogante";
                return $new_firma;
            }
            return null;
        }

        return null;
    }

    public function firmaJefePersonal()
    {
        $last_status_aprobado = self::lastEstadoAprobado();
        if ($last_status_aprobado) {
            $firma = $this->estados()->where('s_role_id', 4)->where('status', EstadoSolicitud::STATUS_APROBADO)->where('created_at', '<=', $last_status_aprobado->created_at)->orderBy('id', 'DESC')->first();
            if ($firma) {
                $nombres        = $firma->funcionario->abreNombres();
                $fecha          = Carbon::parse($firma->created_at)->format('d-m-y H:i:s');
                $is_subrogante  = $firma->is_subrogante ? "(S)" : "";
                $new_firma      = "$nombres $fecha $is_subrogante";
                return $new_firma;
            }
            return null;
        }
        return null;
    }

    public function firmaSubDirector()
    {
        $last_status_aprobado = self::lastEstadoAprobado();
        if ($last_status_aprobado) {
            $firma = $this->estados()->where('s_role_id', 5)->where('status', EstadoSolicitud::STATUS_APROBADO)->where('created_at', '<=', $last_status_aprobado->created_at)->orderBy('id', 'DESC')->first();
            if ($firma) {
                $nombres        = $firma->funcionario->abreNombres();
                $fecha          = Carbon::parse($firma->created_at)->format('d-m-y H:i:s');
                $is_subrogante  = $firma->is_subrogante ? "(S)" : "";
                $new_firma      = "$nombres $fecha $is_subrogante";
                return $new_firma;
            }
            return null;
        }
        return null;
    }

    public function afectaConvenio()
    {
        if ($this->afecta_convenio) {
            return 'AFECTA A CONVENIO';
        } else {
            return 'NO AFECTA A CONVENIO';
        }
    }

    public function isAnulada()
    {
        if ($this->status === self::STATUS_ANULADO) {
            return true;
        }
        return false;
    }

    public function isGrupo()
    {
        $is_grupo =  $this->grupo ? true : false;
        $data = (object)[
            'value'             => $is_grupo,
            'message'           =>  !$is_grupo ? 'S/G' : null,
        ];

        return $data;
    }

    public function menuAdmin()
    {
        $menu = [];
        $user = Auth::user();
        if (self::authorizedToVerDatos() || $user->hasPermissionTo('solicitudes.ver')) {
            $menu[] = [
                'code'      => 'datos',
                'name'      => 'Datos',
                'extra'     => null,
                'icon_name' => 'Document',
                'icon_color'    => '#35495e'
            ];
        }

        if (self::authorizedToVerInformes() || $user->hasPermissionTo('solicitudes.ver')) {
            $n_informes_cometido    = $this->informes()->count();
            $menu[] = [
                'code'      => 'informes',
                'name'      => 'Informe cometido',
                'extra'     => "($n_informes_cometido)",
                'icon_name' => 'StarFilled',
                'icon_color'    => '#fdc109'
            ];
        }

        if (self::authorizedToVerValorizacion() || $user->hasPermissionTo('solicitudes.ver')) {
            $is_calculo             = self::getLastCalculo() ? 'Si' : 'No';
            $menu[] = [
                'code'      => 'calculo',
                'name'      => 'Valorización',
                'extra'     => "($is_calculo)",
                'icon_name' => 'Wallet',
                'icon_color'    => '#35495e'
            ];
        }

        if (self::authorizedToVerRendicion() || $user->hasPermissionTo('solicitudes.ver')) {
            $n_proceso_rendiciones  = $this->procesoRendicionGastos()->count();
            $menu[] = [
                'code'      => 'rendiciones',
                'name'      => 'Rendiciones',
                'extra'     => "($n_proceso_rendiciones)",
                'icon_name' => "WalletFilled",
                'icon_color'    => '#35495e'
            ];
        }

        if (self::authorizedToVerArchivos() || $user->hasPermissionTo('solicitudes.ver')) {
            $n_documentos           = $this->documentos()->count();
            $menu[] = [
                'code'      => 'archivos',
                'name'      => 'Archivos',
                'extra'     => "($n_documentos)",
                'icon_name' => "Files",
                'icon_color'    => '#35495e'
            ];
        }

        if (self::authorizedToVerConvenio() || $user->hasPermissionTo('solicitudes.ver')) {
            $is_convenio            = $this->convenio ? 'Si' : 'No';
            $menu[] = [
                'code'      => 'convenio',
                'name'      => 'Convenio',
                'extra'     => "($is_convenio)",
                'icon_name' => 'OfficeBuilding',
                'icon_color'    => '#35495e'
            ];
        }

        if (self::authorizedToVerFirmantes() || $user->hasPermissionTo('solicitudes.ver')) {
            $n_firmantes            = $this->firmantes()->where('posicion_firma', '>', 0)->count();
            $menu[] = [
                'code'      => 'firmantes',
                'name'      => 'Firmantes',
                'extra'     => "($n_firmantes)",
                'icon_name' => 'UserFilled',
                'icon_color'    => '#35495e'
            ];
        }

        if (self::authorizedToVerHistorial() || $user->hasPermissionTo('solicitudes.ver')) {
            $n_estados              = $this->estados()->count();
            $menu[] = [
                'code'      => 'seguimiento',
                'name'      => 'Historial',
                'extra'     => "($n_estados)",
                'icon_name' => "Timer",
                'icon_color'    => '#35495e'
            ];
        }
        return $menu;
    }

    public function scopeSearchInput($query, $params, $in = null)
    {
        if (!$params) {
            return $query;
        }

        $camposDirectos = ['codigo', 'actividad_realizada', 'vistos', 'observacion_gastos', 'observacion'];

        $query->where(function ($q) use ($params, $camposDirectos) {
            foreach ($camposDirectos as $campo) {
                $q->orWhere($campo, 'like', "%{$params}%");
            }
        });

        $this->aplicarFiltroFuncionario($query, $params);

        if ($in !== 'solicitud') {
            $query->when($this->debeAplicarFiltro('firmantes', $params), function ($q) use ($params) {
                $this->aplicarFiltroFirmantes($q, $params);
            });

            $query->when($this->debeAplicarFiltro('estados', $params), function ($q) use ($params) {
                $this->aplicarFiltroEstados($q, $params);
            });

            $query->when($this->debeAplicarFiltro('convenio', $params), function ($q) use ($params) {
                $this->aplicarFiltroConvenio($q, $params);
            });

            $query->when($this->debeAplicarFiltro('informes', $params), function ($q) use ($params) {
                $this->aplicarFiltroInformes($q, $params);
            });
        }

        return $query;
    }

    private function debeAplicarFiltro($relacion, $params)
    {
        return !empty($params) && in_array($relacion, ['firmantes', 'estados', 'convenio', 'informes']);
    }

    private function aplicarFiltroFirmantes($query, $params)
    {
        $query->orWhereHas('firmantes.funcionario', function ($q) use ($params) {
            $q->select(['id', 'nombres', 'apellidos', 'rut', 'rut_completo', 'email']);
            $this->aplicarFiltroNombre($q, $params);
            $this->aplicarFiltroRutEmail($q, $params);
        });
    }

    private function aplicarFiltroEstados($query, $params)
    {
        $query->orWhereHas('estados', function ($q) use ($params) {
            $q->select(['id', 'observacion'])
                ->where('observacion', 'like', "%{$params}%");
        });
    }

    private function aplicarFiltroConvenio($query, $params)
    {
        $query->orWhereHas('convenio', function ($q) use ($params) {
            $q->select(['id', 'codigo', 'n_resolucion', 'email', 'tipo_contrato', 'observacion'])
                ->where('codigo', 'like', "%{$params}%")
                ->orWhere('n_resolucion', 'like', "%{$params}%")
                ->orWhere('email', 'like', "%{$params}%")
                ->orWhere('tipo_contrato', 'like', "%{$params}%")
                ->orWhere('observacion', 'like', "%{$params}%");
        });
    }

    private function aplicarFiltroInformes($query, $params)
    {
        $query->orWhereHas('informes', function ($q) use ($params) {
            $q->select(['id', 'codigo', 'actividad_realizada'])
                ->where('codigo', 'like', "%{$params}%")
                ->orWhere('actividad_realizada', 'like', "%{$params}%");
        });
    }

    private function aplicarFiltroFuncionario($query, $params)
    {
        $query->orWhere(function ($q) use ($params) {
            $q->whereHas('funcionario', function ($q) use ($params) {
                $this->aplicarFiltroNombre($q, $params);
                $this->aplicarFiltroRutEmail($q, $params);
            });
        });
    }

    private function aplicarFiltroNombre($query, $params)
    {
        $palabras = explode(' ', $params);
        $query->where(function ($q) use ($palabras) {
            foreach ($palabras as $palabra) {
                $q->where(DB::raw("CONCAT(nombres, ' ', apellidos)"), 'like', "%{$palabra}%");
            }
        });
    }

    private function aplicarFiltroRutEmail($query, $params)
    {
        $query->orWhere('rut_completo', 'like', "%{$params}%")
            ->orWhere('rut', 'like', "%{$params}%")
            ->orWhere('email', 'like', "%{$params}%");
    }

    public function scopeIsReasignada($query, $params)
    {
        if ($params) {
            if (in_array(0, $params) && in_array(1, $params)) {
                return $query;
            } elseif (in_array(0, $params)) {
                return $query->where('is_reasignada', false);
            } elseif (in_array(1, $params)) {
                return $query->where('is_reasignada', true);
            }
        }
    }

    public function scopeIsGrupo($query, $params)
    {
        if ($params) {
            if (in_array(0, $params) && in_array(1, $params)) {
                return $query;
            } elseif (in_array(0, $params)) {
                return $query->whereNull('grupo_id');
            } elseif (in_array(1, $params)) {
                return $query->whereNotNull('grupo_id');
            }
        }
    }

    public function scopeJefaturaDirecta($query, $params)
    {
        if ($params) {
            if (in_array(1, $params)) {
                return $query->whereHas('estados', function ($q) {
                    $q->where(function ($query) {
                        $query->where('s_role_id', 3)
                            ->orWhere('r_s_role_id', 3);
                    })
                        ->where('status', EstadoSolicitud::STATUS_APROBADO);
                });
            }
        } else {
            return $query;
        }
    }

    public function scopeIsLoadSirh($query, $params)
    {
        if ($params) {
            return $query->whereIn('load_sirh', $params);
        }
    }

    public function scopePeriodoSolicitud($query, $params)
    {
        if ($params) {
            return $query->whereBetween('fecha_inicio', array($params[0], $params[1]));
        }
    }

    public function scopePeriodoIngreso($query, $params)
    {
        if ($params) {
            $inicio     = Carbon::parse($params[0])->startOfDay();
            $termino    = Carbon::parse($params[1])->endOfDay();

            return $query->whereBetween('fecha_by_user', array($inicio, $termino));
        }
    }

    public function scopePeriodoInformeCometido($query, $params)
    {
        if ($params)
            return $query->whereHas('informes', function ($q) use ($params) {
                $q->whereBetween('fecha_inicio', array($params[0], $params[1]));
            });
    }

    public function scopeEstadoInformeCometido($query, $params)
    {
        if ($params)
            return $query->whereHas('informes', function ($q) use ($params) {
                $q->whereIn('last_status', $params);
            });
    }

    public function scopeEstadoIngresoInformeCometido($query, $params)
    {
        if ($params)
            return $query->whereHas('informes', function ($q) use ($params) {
                $q->whereIn('status_ingreso', $params);
            });
    }

    public function scopeDerechoViatico($query, $params)
    {
        if ($params) {
            return $query->whereIn('derecho_pago', $params);
        }
    }

    public function scopeValorizacion($query, $params)
    {
        if ($params) {
            if (in_array(0, $params) && in_array(1, $params)) {
                return $query;
            } elseif (in_array(0, $params)) {
                return $query->whereDoesntHave('calculos');
            } elseif (in_array(1, $params)) {
                return $query->whereHas('calculos');
            }
        }
    }

    public function scopeRendicion($query, $params)
    {
        if ($params) {
            if (in_array(0, $params) && in_array(1, $params)) {
                return $query;
            } elseif (in_array(0, $params)) {
                return $query->whereDoesntHave('procesoRendicionGastos');
            } elseif (in_array(1, $params)) {
                return $query->whereHas('procesoRendicionGastos');
            }
        }
    }

    public function scopeConvenio($query, $params)
    {
        if ($params) {
            if (in_array(0, $params) && in_array(1, $params)) {
                return $query;
            } elseif (in_array(0, $params)) {
                return $query->whereNull('convenio_id');
            } elseif (in_array(1, $params)) {
                return $query->whereNotNull('convenio_id');
            }
        }
    }

    public function scopeInformesCometido($query, $params)
    {
        if ($params) {
            if (in_array(0, $params) && in_array(1, $params)) {
                return $query;
            } elseif (in_array(0, $params)) {
                return $query->whereDoesntHave('informes');
            } elseif (in_array(1, $params)) {
                return $query->whereHas('informes');
            }
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
            return $query->whereHas('motivos', function ($q) use ($params) {
                $q->whereIn('motivos.id', $params);
            });
    }

    public function scopeLugar($query, $params)
    {
        if ($params)
            return $query->whereHas('lugares', function ($q) use ($params) {
                $q->whereIn('lugars.id', $params);
            });
    }

    public function scopePais($query, $params)
    {
        if ($params)
            return $query->whereHas('paises', function ($q) use ($params) {
                $q->whereIn('countries.id', $params);
            });
    }

    public function scopeMedioTransporte($query, $params)
    {
        if ($params)
            return $query->whereHas('transportes', function ($q) use ($params) {
                $q->whereIn('transportes.id', $params);
            });
    }

    public function scopeTipoComision($query, $params)
    {
        if ($params)
            return $query->whereHas('tipoComision', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeJornada($query, $params)
    {
        if ($params)
            return $query->whereIn('jornada', $params);
    }

    public function scopeEstado($query, $params)
    {
        if ($params)
            return $query->whereIn('status', $params);
    }

    public function scopeEstablecimiento($query, $params)
    {
        if ($params)
            return $query->whereHas('establecimiento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeDepartamento($query, $params)
    {
        if ($params)
            return $query->whereHas('departamento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeSubdepartamento($query, $params)
    {
        if ($params)
            return $query->whereHas('subdepartamento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeLey($query, $params)
    {
        if ($params)
            return $query->whereHas('ley', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeEstamento($query, $params)
    {
        if ($params)
            return $query->whereHas('estamento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeCalidad($query, $params)
    {
        if ($params)
            return $query->whereHas('calidad', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeFirmantesPendiente($query, $params)
    {
        if ($params)
            return $query->where('status', self::STATUS_EN_PROCESO)
                ->whereHas('firmantes', function ($q) use ($params) {
                    $q->where(function ($query) use ($params) {
                        $query->whereRaw('solicituds.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                            ->where('solicituds.is_reasignada', 0)
                            ->where('status', true)
                            ->where('is_executed', false)
                            ->where('role_id', '!=', 1)
                            ->whereIn('user_id', $params);
                    })->orWhere(function ($query) use ($params) {
                        $query->whereRaw('solicituds.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                            ->where('solicituds.is_reasignada', 1)
                            ->where('is_reasignado', true)
                            ->where('status', true)
                            ->where('is_executed', false)
                            ->where('role_id', '!=', 1)
                            ->whereIn('user_id', $params);
                    });
                });
    }
}
