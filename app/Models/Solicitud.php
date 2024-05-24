<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use App\Policies\InformeCometidoPolicy;

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
        'fijada',
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
        'observacion_gastos',
        'status',
        'last_status',
        'fecha_resolucion',
        'n_resolucion',
        'tipo_resolucion',
        'jornada',
        'posicion_firma_actual',
        'dentro_pais',
        'n_cargo_user',
        'total_dias_cometido',
        'total_horas_cometido',
        'valor_cometido_diario',
        'valor_cometido_parcial',
        'valor_pasaje',
        'valor_total',
        'vistos',
        'total_firmas',
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
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
        'is_reasignada'
    ];

    public function grupoDepto($solicitud)
    {
        $grupo = Grupo::where('establecimiento_id', $solicitud->establecimiento_id)
            ->where('departamento_id', $solicitud->departamento_id)
            ->where('sub_departamento_id', $solicitud->sub_departamento_id)
            ->whereHas('firmantes', function ($q) {
                $q->where('status', true);
            })
            ->first();

        return $grupo;
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
        });

        static::created(function ($solicitud) {
            $dias_permitidos                = (int)Configuration::obtenerValor('informecometido.dias_atraso', $solicitud->establecimiento_id);
            $vistos                         = Configuration::obtenerValor('info.vistos', $solicitud->establecimiento_id);
            $grupo                          = self::grupoDepto($solicitud);
            $solicitud->correlativo         = self::generarCorrelativo($solicitud);
            $solicitud->codigo              = self::generarCodigo($solicitud);
            $solicitud->grupo_id            = $grupo ? $grupo->id : null;
            $solicitud->tipo_resolucion     = self::RESOLUCION_EXENTA;
            $solicitud->total_firmas        = $solicitud->firmantes()->where('status', true)->count();
            $solicitud->dias_permitidos     = $dias_permitidos;
            $solicitud->vistos              = $vistos;
            $solicitud->save();
        });

        static::updating(function ($solicitud) {
            $solicitud->codigo              = self::generarCodigoUpdate($solicitud);
            $solicitud->total_firmas        = $solicitud->firmantes()->where('status', true)->count();
        });
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

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
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

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
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

    public function authorizedToCreateInformeCometido()
    {
        $policy = resolve(InformeCometidoPolicy::class);
        return $policy->create(auth()->user(), new InformeCometido, $this);
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
            $date_status            = Carbon::parse($last_status->created_at);
            $date_plazo             = $date_status->addDays($dias_atraso_actividad);
            $data = (object)[
                'is_not_actividad'  => true,
                'title'             => '¡Sin actividad!',
                'message'           => "Se anulará automáticamente el " . $date_plazo->format('d-m-Y') . " por no editar."
            ];

            return $data;
        }
        return $data;
    }

    public function valorTotal()
    {
        $r_total        = 0;
        $total_calculo = 0;
        $calculo = self::getLastCalculo();
        if ($calculo) {
            $total_calculo = $calculo->monto_total;
        }
        $total = $r_total + $total_calculo;
        $total = "$" . number_format($total, 0, ",", ".");
        return $total;
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
            'message'           =>  !$is_grupo ? 'Sin grupo de firma' : null,
        ];

        return $data;
    }

    public function menuAdmin()
    {
        $menu = [];
        if (self::authorizedToVerDatos()) {
            $menu[] = [
                'code'      => 'datos',
                'name'      => 'Datos',
                'extra'     => null
            ];
        }

        if (self::authorizedToVerFirmantes()) {
            $n_firmantes            = $this->firmantes()->where('posicion_firma', '>', 0)->count();
            $menu[] = [
                'code'      => 'firmantes',
                'name'      => 'Firmantes',
                'extra'     => "($n_firmantes)"
            ];
        }

        if (self::authorizedToVerInformes()) {
            $n_informes_cometido    = $this->informes()->count();
            $menu[] = [
                'code'      => 'informes',
                'name'      => 'Informes',
                'extra'     => "($n_informes_cometido)"
            ];
        }

        if (self::authorizedToVerValorizacion()) {
            $is_calculo             = self::getLastCalculo() ? 'Si' : 'No';
            $menu[] = [
                'code'      => 'calculo',
                'name'      => 'Valorización',
                'extra'     => "($is_calculo)"
            ];
        }

        if (self::authorizedToVerConvenio()) {
            $is_convenio            = $this->convenio ? 'Si' : 'No';
            $menu[] = [
                'code'      => 'convenio',
                'name'      => 'Convenio',
                'extra'     => "($is_convenio)"
            ];
        }

        if (self::authorizedToVerRendicion()) {
            $n_proceso_rendiciones  = $this->procesoRendicionGastos()->count();
            $menu[] = [
                'code'      => 'rendiciones',
                'name'      => 'Rendiciones',
                'extra'     => "($n_proceso_rendiciones)"
            ];
        }

        if (self::authorizedToVerArchivos()) {
            $n_documentos           = $this->documentos()->count();
            $menu[] = [
                'code'      => 'archivos',
                'name'      => 'Archivos',
                'extra'     => "($n_documentos)"
            ];
        }

        if (self::authorizedToVerHistorial()) {
            $n_estados              = $this->estados()->count();
            $menu[] = [
                'code'      => 'seguimiento',
                'name'      => 'Historial',
                'extra'     => "($n_estados)"
            ];
        }
        return $menu;
    }

    public function scopeSearchInput($query, $params)
    {
        if ($params)
            return $query->where('codigo', 'like', '%' . $params . '%')
                ->orWhere('actividad_realizada', 'like', '%' . $params . '%')
                ->orWhere('vistos', 'like', '%' . $params . '%')
                ->orWhere('observacion_gastos', 'like', '%' . $params . '%')
                ->orWhere(function ($query) use ($params) {
                    $query->whereHas('funcionario', function ($query) use ($params) {
                        $query->where('rut_completo', 'like', '%' . $params . '%')
                            ->orWhere('rut', 'like', '%' . $params . '%')
                            ->orWhere('nombres', 'like', '%' . $params . '%')
                            ->orWhere('apellidos', 'like', '%' . $params . '%')
                            ->orWhere('nombre_completo', 'like', '%' . $params . '%')
                            ->orWhere('email', 'like', '%' . $params . '%');
                    });
                })->orWhere(function ($query) use ($params) {
                    $query->whereHas('firmantes.funcionario', function ($query) use ($params) {
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
}
