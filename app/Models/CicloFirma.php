<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class CicloFirma extends Model
{
    protected $table        = "ciclo_firmas";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'establecimiento_id',
        'role_id',
        'user_id_by',
        'fecha_by_user',
    ];

    use HasFactory;

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
