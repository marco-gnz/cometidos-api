<?php

namespace App\Models;

use App\Policies\InformeCometidoPolicy;
use App\Policies\ProcesoRendicionGastoPolicy;
use App\Policies\SolicitudPolicy;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Gate;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'rut',
        'dv',
        'rut_completo',
        'nombres',
        'apellidos',
        'nombre_completo',
        'estado',
        'email',
        'telefono',
        'password',
        'is_firmante',
        'is_subrogante',
        'is_solicitud',
        'is_informe',
        'is_rendicion',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($usuario) {
            $usuario->uuid                  = Str::uuid();
            $usuario->rut_completo          = $usuario->rut . '-' . $usuario->dv;
            $usuario->nombre_completo       = $usuario->nombres . ' ' . $usuario->apellidos;
            $usuario->password              = bcrypt($usuario->rut);
        });

        static::updating(function ($usuario) {
            $usuario->rut_completo          = $usuario->rut . '-' . $usuario->dv;
            $usuario->nombre_completo       = $usuario->nombres . ' ' . $usuario->apellidos;
        });
    }

    public function firmasGrupo()
    {
        return $this->hasMany(Firmante::class);
    }

    public function firmas()
    {
        return $this->hasMany(SolicitudFirmante::class);
    }

    public function reasignacionAusencias()
    {
        return $this->hasMany(Reasignacion::class, 'user_ausente_id');
    }

    public function reasignacionAsignadas()
    {
        return $this->hasMany(Reasignacion::class, 'user_subrogante_id');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    public function convenios()
    {
        return $this->hasMany(Convenio::class);
    }

    public function ausentismos()
    {
        return $this->hasMany(Ausentismo::class, 'user_ausente_id');
    }

    public function estados()
    {
        return $this->hasMany(EstadoSolicitud::class);
    }

    public function historys()
    {
        return $this->hasMany(HistoryActionUser::class);
    }

    public function cuentas()
    {
        return $this->hasMany(CuentaBancaria::class);
    }

    public function establecimientos()
    {
        return $this->belongsToMany(Establecimiento::class);
    }

    public function leyes()
    {
        return $this->belongsToMany(Ley::class);
    }

    public function departamentos()
    {
        return $this->belongsToMany(Departamento::class);
    }

    public function addCuentas(array $cuentas)
    {
        return $this->cuentas()->createMany($cuentas);
    }

    public function addHistorys(array $historys)
    {
        return $this->historys()->createMany($historys);
    }

    public function addAusentismos(array $ausentismos)
    {
        return $this->ausentismos()->createMany($ausentismos);
    }

    public function lastCuentaBancaria()
    {
        return $this->cuentas()->where('status', true)->orderBy('id', 'DESC')->first();
    }

    public function lastHistory(int $type)
    {
        return $this->historys()->where('type', $type)->orderBy('id', 'DESC')->first();
    }

    public function lastContrato()
    {
        return $this->contratos()->orderBy('id', 'DESC')->first();
    }

    public function scopeGeneral($query, $input)
    {
        if ($input)
            return $query->where('rut_completo', 'like', '%' . $input . '%')
                ->orWhere('rut', 'like', '%' . $input . '%')
                ->orWhere('nombres', 'like', '%' . $input . '%')
                ->orWhere('apellidos', 'like', '%' . $input . '%')
                ->orWhere('nombre_completo', 'like', '%' . $input . '%')
                ->orWhere('email', 'like', '%' . $input . '%');
    }

    public function scopeEstablecimiento($query, $params)
    {
        if ($params)
            return $query->whereHas('contratos.establecimiento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    //pivote
    public function scopeEstablecimientos($query, $params)
    {
        if ($params)
            return $query->whereHas('establecimientos', function ($q) use ($params) {
                $q->whereIn('establecimientos.id', $params);
            });
    }

    public function scopeDepartamentos($query, $params)
    {
        if ($params)
            return $query->whereHas('departamentos', function ($q) use ($params) {
                $q->whereIn('departamentos.id', $params);
            });
    }

    public function scopeLeyes($query, $params)
    {
        if ($params)
            return $query->whereHas('leyes', function ($q) use ($params) {
                $q->whereIn('leys.id', $params);
            });
    }

    public function scopePerfiles($query, $params)
    {
        if ($params)
            return $query->whereHas('roles', function ($q) use ($params) {
                $q->whereIn('roles.id', $params);
            });
    }

    public function scopeDepto($query, $params)
    {
        if ($params)
            return $query->whereHas('contratos.departamento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeSubdepto($query, $params)
    {
        if ($params)
            return $query->whereHas('contratos.subDepartamento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeGrado($query, $params)
    {
        if ($params)
            return $query->whereHas('contratos.grado', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeLey($query, $params)
    {
        if ($params)
            return $query->whereHas('contratos.ley', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function authorizedToCreateSolicitud()
    {
        $policy = resolve(SolicitudPolicy::class);
        return $policy->create(auth()->user());
    }

    public function authorizedToCreateRendicion()
    {
        $policy = resolve(ProcesoRendicionGastoPolicy::class);
        return $policy->create(auth()->user());
    }

    public function authorizedToCreateInforme()
    {
        $policy = resolve(InformeCometidoPolicy::class);
        return $policy->createother(auth()->user());
    }

    public function abreNombres()
    {
        $nombres    = mb_substr($this->nombres, 0, 3);
        $apellidos  = mb_substr($this->apellidos, 0, 17);
        return "$nombres. $apellidos.";
    }

    public function totalViaticosProcesados()
    {
        return $this->solicitudes()->where('status', Solicitud::STATUS_PROCESADO)->count();
    }

    public function totalValorizacion()
    {
        $total = 0;
        $solicitudes =  $this->solicitudes()
        ->where('status', Solicitud::STATUS_PROCESADO)
        ->get();

        foreach ($solicitudes as $solicitud) {
            if($solicitud->getLastCalculo()){
                $total += $solicitud->getLastCalculo()->monto_total;
            }
        }
        return "$".number_format($total, 0, ',', '.');
    }

    public function totalRendiciones()
    {
        $total = 0;
        $solicitudes =  $this->solicitudes()
            ->where('status', Solicitud::STATUS_PROCESADO)
            ->get();

        foreach ($solicitudes as $solicitud) {
            $total += $solicitud->sumTotalProcesosRendicionesNumber();
        }
        return "$" . number_format($total, 0, ',', '.');
    }

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
    }

    public function authorizedToUpdatePerfil()
    {
        return Gate::allows('updatePerfil', $this);
    }

    public function authorizedToDeletePerfil()
    {
        return Gate::allows('deletePerfil', $this);
    }

    public function viewPermisos()
    {
        $permissions_extras     = $this->getPermissionNames()->toArray();
        $permissions_to_roles   = $this->getPermissionsViaRoles()->pluck('name')->toArray();
        $permissions            = array_merge($permissions_extras, $permissions_to_roles);
        return $permissions;
    }

    /* public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    } */
}
