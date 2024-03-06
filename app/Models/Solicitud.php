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
        self::STATUS_PROCESADO          => 'PROCESADO',
        self::STATUS_ANULADO            => 'ANULADO',
    ];

    protected $fillable = [
        'uuid',
        'codigo',
        'fecha_inicio',
        'fecha_termino',
        'hora_llegada',
        'hora_salida',
        'derecho_pago',
        'utiliza_transporte',
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
        'total_firmas',
        'total_ok',
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
    ];

    public function grupoDepto($solicitud)
    {
        $grupo = Grupo::where('establecimiento_id', $solicitud->funcionario->establecimiento_id)
            ->where('departamento_id', $solicitud->funcionario->departamento_id)
            ->where('sub_departamento_id', $solicitud->funcionario->sub_departamento_id)
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
            $dias_permitidos                = (int)Configuration::obtenerValor('informecometido.dias_atraso');
            $grupo                          = self::grupoDepto($solicitud);
            $solicitud->codigo              = self::generarCodigo($solicitud);
            $solicitud->departamento_id     = $grupo ? $grupo->departamento_id : null;
            $solicitud->sub_departamento_id = $grupo ? $grupo->sub_departamento_id : null;
            $solicitud->establecimiento_id  = $grupo ? $grupo->establecimiento_id : null;
            $solicitud->grupo_id            = $grupo ? $grupo->id : null;
            $solicitud->ley_id              = $solicitud->funcionario ? $solicitud->funcionario->ley_id : null;
            $solicitud->grado_id            = $solicitud->funcionario ? $solicitud->funcionario->grado_id : null;
            $solicitud->cargo_id            = $solicitud->funcionario ? $solicitud->funcionario->cargo_id : null;
            $solicitud->estamento_id        = $solicitud->funcionario ? $solicitud->funcionario->estamento_id : null;
            $solicitud->hora_id             = $solicitud->funcionario ? $solicitud->funcionario->hora_id : null;
            $solicitud->calidad_id          = $solicitud->funcionario ? $solicitud->funcionario->calidad_id : null;
            $solicitud->tipo_resolucion     = self::RESOLUCION_EXENTA;
            $solicitud->total_firmas        = $solicitud->firmantes()->where('status', true)->count();
            $solicitud->dias_permitidos     = $dias_permitidos;
            $solicitud->save();
        });

        static::updating(function ($solicitud) {
            $solicitud->codigo              = self::generarCodigoUpdate($solicitud);
            $solicitud->total_firmas        = $solicitud->firmantes()->where('status', true)->count();
        });
    }


    private static function generarCodigo($solicitud)
    {
        $letra                  = $solicitud->derecho_pago ? 'C' : 'S';
        $correlativo            = str_pad(self::whereYear('created_at', $solicitud->created_at->year)->count() + 1, 5, '0', STR_PAD_LEFT);
        $anio                   = $solicitud->created_at->year;
        $codigoEstablecimiento  = $solicitud->funcionario->establecimiento->cod_sirh;
        $codigo                 = "{$codigoEstablecimiento}-{$anio}-{$correlativo}-{$letra}";
        return $codigo;
    }

    private static function generarCodigoUpdate($solicitud)
    {
        $codigo_actual  = $solicitud->codigo;
        $letra          = $solicitud->derecho_pago ? 'C' : 'S';
        list($codigoEstablecimiento, $anio, $correlativo) = explode('-', $codigo_actual);
        $codigo         = "{$codigoEstablecimiento}-{$anio}-{$correlativo}-{$letra}";
        return $codigo;
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

    public function informeCometido()
    {
        return $this->informes()->whereIn('last_status', [EstadoInformeCometido::STATUS_INGRESADA, EstadoInformeCometido::STATUS_APROBADO])->orderBy('fecha_by_user', 'DESC')->first();
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

    private function totalFirmasAprobadas()
    {
        $last_status    = $this->estados()->orderBy('id', 'DESC')->first();
        $total_aprobado = $this->estados()->where('posicion_firma', '<=', $last_status->posicion_firma)->where('status', EstadoSolicitud::STATUS_APROBADO)->count();
        return $total_aprobado;
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

    public function pageFirmaPorcentaje()
    {
        $count_firmantes = self::totalFirmas();
        $firmas_aprobadas = self::totalFirmasAprobadas();
        $porcentaje = ($firmas_aprobadas / $count_firmantes) * 100;

        return $porcentaje;
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
        switch ($this->status) {
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
        $documentos = [
            [
                'name'          => 'Informe C.',
                'url'           => $informe ? route('informecometido.show', ['uuid' => $informe->uuid]) : null,
                'exist'         => $informe ? true : false,
                'stauts_nom'    => $informe ? EstadoInformeCometido::STATUS_NOM[$informe->last_status] : '',
                'type'          => $informe ? EstadoInformeCometido::STATUS_TYPE[$informe->last_status] : '',
            ],
            [
                'name'  => 'Resolución C.',
                'url'   => route('resolucioncometidofuncional.show', ['uuid' => $this->uuid]),
                'exist' => true,
                'type'  => 'success'
            ],
            [
                'name'  => 'Convenio C.',
                'url'   => $this->convenio ? route('convenio.show', ['uuid' => $this->convenio->uuid]) : null,
                'exist' => $this->convenio ? true : false,
                'type'  => 'success'
            ]
        ];

        return $documentos;
    }

    public function valorTotal()
    {
        $r_total    = 0;
        $r_procesos = $this->procesoRendicionGastos()->where('last_status', 1)->get();
        if (count($r_procesos) > 0) {
            foreach ($r_procesos as $proceso) {
                $r_total += $proceso->rendiciones()->where('last_status', 1)->sum('mount_real');
            }
        }

        $total_calculo = 0;
        $calculo = self::getLastCalculo();
        if ($calculo) {
            $total_calculo = $calculo->monto_total;
        }
        $total = $r_total + $total_calculo;
        $total = "$" . number_format($total, 0, ",", ".");
        return $total;
    }
}
