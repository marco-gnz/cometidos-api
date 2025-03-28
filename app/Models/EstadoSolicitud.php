<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class EstadoSolicitud extends Model
{
    protected $table        = "estado_solicituds";
    protected $primaryKey   = 'id';

    public const STATUS_INGRESADA  = 0;
    public const STATUS_PENDIENTE  = 1;
    public const STATUS_APROBADO   = 2;
    public const STATUS_RECHAZADO  = 3;
    public const STATUS_ANULADO    = 4;
    public const STATUS_MODIFICADA = 5;

    public const STATUS_NOM = [
        self::STATUS_INGRESADA  => 'INGRESADA',
        self::STATUS_PENDIENTE  => 'PENDIENTE',
        self::STATUS_APROBADO   => 'APROBADO',
        self::STATUS_RECHAZADO  => 'RECHAZADO',
        self::STATUS_ANULADO    => 'ANULADO',
        self::STATUS_MODIFICADA => 'MODIFICADA'
    ];

    public const STATUS_DESC = [
        self::STATUS_INGRESADA  => 'Ingresada al sistema',
        self::STATUS_PENDIENTE  => 'Pendiente por validar',
        self::STATUS_APROBADO   => 'Aprobado por administrador',
        self::STATUS_RECHAZADO  => 'Rechazado por administrador',
        self::STATUS_ANULADO    => 'Anulado por administrador',
        self::STATUS_MODIFICADA => 'Modificada por usuario'
    ];

    public const RECHAZO_1 = 1;
    public const RECHAZO_2 = 2;
    public const RECHAZO_3 = 3;
    public const RECHAZO_4 = 4;
    public const RECHAZO_5 = 5;
    public const RECHAZO_6 = 6;
    public const RECHAZO_7 = 7;
    public const RECHAZO_8 = 8;
    public const RECHAZO_9 = 9;

    public const RECHAZO_NOM = [
        self::RECHAZO_1 => 'FALTAN ANTECEDENTES',
        self::RECHAZO_2 => 'DOCUMENTOS ILEGIBLES',
        self::RECHAZO_3 => 'ACTIVIDAD SUSPENDIDA',
        self::RECHAZO_4 => 'NO CORRESPONDE CON DERECHO A VIATICO',
        self::RECHAZO_5 => 'FECHAS U HORARIOS ERRONEOS',
        self::RECHAZO_6 => 'FUERA DE PLAZO',
        self::RECHAZO_7 => 'ERRORES EN DATOS INGRESADOS',
        self::RECHAZO_8 => 'ASISTENCIA A LA ACTIVIDAD NO AUTORIZADA',
        self::RECHAZO_9 => 'SOLICITUD NO CORRESPONDE A COMETIDO '
    ];

    public const RECHAZO_DESC = [
        self::RECHAZO_1 => 'La solicitud no cuenta con toda la información requerida para su validación.',
        self::RECHAZO_2 => 'Los documentos adjuntos no se pueden leer correctamente, ya sea por mala calidad, borrosidad o escaneo deficiente.',
        self::RECHAZO_3 => 'La actividad para la cual se solicitó el cometido ha sido suspendida.',
        self::RECHAZO_4 => 'La solicitud no cumple con los requisitos para acceder al pago del viático.',
        self::RECHAZO_5 => 'Las fechas o los horarios indicados en la solicitud no son correctos.',
        self::RECHAZO_6 => 'La solicitud fue ingresada fuera del período establecido para su tramitación.',
        self::RECHAZO_7 => 'Se han detectado inconsistencias o errores en los datos proporcionados en la solicitud.',
        self::RECHAZO_8 => 'Funcionaria(o) no cuenta con la autorización correspondiente para asistir a la actividad mencionada.',
        self::RECHAZO_9 => 'La solicitud no se ajusta a las actividades o funciones que justifican un cometido.'
    ];


    public const RECHAZO_STATUS = [
        ['id' => self::RECHAZO_1, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_1], 'desc' => self::RECHAZO_DESC[self::RECHAZO_1]],
        ['id' => self::RECHAZO_2, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_2], 'desc' => self::RECHAZO_DESC[self::RECHAZO_2]],
        ['id' => self::RECHAZO_3, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_3], 'desc' => self::RECHAZO_DESC[self::RECHAZO_3]],
        ['id' => self::RECHAZO_4, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_4], 'desc' => self::RECHAZO_DESC[self::RECHAZO_4]],
        ['id' => self::RECHAZO_5, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_5], 'desc' => self::RECHAZO_DESC[self::RECHAZO_5]],
        ['id' => self::RECHAZO_6, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_6], 'desc' => self::RECHAZO_DESC[self::RECHAZO_6]],
        ['id' => self::RECHAZO_7, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_7], 'desc' => self::RECHAZO_DESC[self::RECHAZO_7]],
        ['id' => self::RECHAZO_8, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_8], 'desc' => self::RECHAZO_DESC[self::RECHAZO_8]],
        ['id' => self::RECHAZO_9, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_9], 'desc' => self::RECHAZO_DESC[self::RECHAZO_9]]
    ];

    protected $fillable = [
        'status',
        'motivo_rechazo',
        'observacion',
        'posicion_firma',
        'posicion_firma_s',
        'posicion_firma_r_s',
        'history_solicitud_old',
        'history_solicitud_new',
        'is_reasignado',
        'is_subrogante',
        'movimiento_system',
        'solicitud_id',
        'firmante_id',
        'user_id',
        's_role_id',
        's_firmante_id',
        'r_s_role_id',
        'r_s_user_id',
        'r_s_firmante_id',
        'ip_address'
    ];

    protected static function booted()
    {
        static::creating(function ($estado) {
            $ip_address                 = Request::ip();
            $estado->ip_address         = $ip_address;

            $is_super_admin             =  Auth::check() ? Auth::user()->hasRole('SUPER ADMINISTRADOR') : null;
            $posicion_firma_s           = $estado->posicion_firma_s;
            $estado->posicion_firma_s   = $posicion_firma_s;
            $estado->posicion_firma     = $posicion_firma_s;
            $estado->s_role_id          = $estado->firmaS ? $estado->firmaS->perfil->id : ($is_super_admin ? 8 : null);
            /* $estado->user_id            = $estado->firmaS ? $estado->firmaS->funcionario->id : auth()->user()->id; */
            $estado->firmante_id        = $estado->firmaS ? $estado->firmaS->id : null;

            if ($estado->is_reasignado) {
                $posicion_firma_r_s         = $estado->posicion_firma_r_s;
                $estado->posicion_firma_r_s = $posicion_firma_r_s;
                $estado->posicion_firma     = $posicion_firma_r_s;
                $estado->r_s_role_id        = $estado->firmaRs->perfil->id;
                $estado->r_s_user_id        = $estado->firmaRs->funcionario->id;
                $estado->firmante_id        = $estado->firmaRs->id;
                $firmantes = $estado->solicitud->firmantes()->get();
                if ($firmantes->count() > 0) {
                    $firmantes->toQuery()->update([
                        'is_reasignado' => false
                    ]);
                }
                $estado->firmaRs->update([
                    'is_reasignado' => true
                ]);
            } else {
                $firmantes = $estado->solicitud->firmantes()->get();
                if ($firmantes->count() > 0) {
                    $firmantes->toQuery()->update([
                        'is_reasignado' => false
                    ]);
                }
            }

            if ($estado->is_reasignado) {
                $estado->solicitud->firmantes->where('status', true)->where('posicion_firma', '>=', $estado->posicion_firma)->toQuery()->update([
                    'is_executed'   => false,
                    'is_success'    => false
                ]);
            } else {
                if ($estado->firmaActual) {
                    $estado->firmaActual->update([
                        'is_executed' => true
                    ]);
                }

                if ($estado->posicion_firma_s === 0) {
                    if ($estado->firmaActual) {
                        $estado->firmaActual->update([
                            'is_success'    => true
                        ]);
                    }
                }
                if ($estado->status === self::STATUS_APROBADO) {
                    if ($estado->firmaActual) {
                        $estado->firmaActual->update([
                            'is_success'    => true
                        ]);
                    }
                }
            }
        });

        static::created(function ($estado) {
            $solicitud = $estado->solicitud->fresh();
            if ($estado->status === self::STATUS_APROBADO) {
                $total_firmas   = $estado->solicitud->firmantes()->where('role_id', '!=', 1)->where('status', true)->count();
                if ($estado->solicitud->totalFirmasAprobadas() === $total_firmas) {
                    $status = Solicitud::STATUS_PROCESADO;
                } else {
                    $status = Solicitud::STATUS_EN_PROCESO;
                }
            } else {
                $status = Solicitud::STATUS_EN_PROCESO;
            }


            $estado->solicitud->update([
                'last_status'               => $estado->status,
                'posicion_firma_actual'     => $estado->posicion_firma,
                'is_reasignada'             => $estado->is_reasignado ? true : false,
                'status'                    => $status
            ]);

            if ($estado->status === self::STATUS_ANULADO) {
                $estado->solicitud->update([
                    'status'    => Solicitud::STATUS_ANULADO
                ]);
                $procesos_rendicion = $estado->solicitud->procesoRendicionGastos()->where('status', '!=', EstadoProcesoRendicionGasto::STATUS_ANULADO)->get();
                if (count($procesos_rendicion) > 0) {
                    $procesos_rendicion->toQuery()->update([
                        'status' => EstadoProcesoRendicionGasto::STATUS_ANULADO
                    ]);
                }
                $informes_cometido = $estado->solicitud->informes()->where('last_status', '!=', EstadoInformeCometido::STATUS_RECHAZADO)->get();
                if (count($informes_cometido) > 0) {
                    $informes_cometido->toQuery()->update([
                        'last_status' => EstadoInformeCometido::STATUS_ANULADO
                    ]);
                }
            }
        });
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function funcionarioRs()
    {
        return $this->belongsTo(User::class, 'r_s_user_id');
    }

    public function perfil()
    {
        return $this->belongsTo(Role::class, 's_role_id');
    }

    public function perfilRs()
    {
        return $this->belongsTo(Role::class, 'r_s_role_id');
    }

    public function firmaActual()
    {
        return $this->belongsTo(SolicitudFirmante::class, 'firmante_id');
    }

    public function firmaS()
    {
        return $this->belongsTo(SolicitudFirmante::class, 's_firmante_id');
    }

    public function firmaRs()
    {
        return $this->belongsTo(SolicitudFirmante::class, 'r_s_firmante_id');
    }

    public function typeStatus()
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
}
