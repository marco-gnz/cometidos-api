<?php

namespace App\Http\Controllers\User\Cuenta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangeDataRequest;
use App\Models\Grupo;
use App\Models\HistoryActionUser;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class CuentaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function changeData(ChangeDataRequest $request)
    {

        $user = auth()->user();

        $grupo = Grupo::where('establecimiento_id', $user->establecimiento_id)
            ->where('departamento_id', $user->departamento_id)
            ->where('sub_departamento_id', $user->sub_departamento_id)
            ->first();

        if (!$grupo) {
            return response(["errors" => ["observacion" => ["No registras grupo de firmante. Contacte a Depto. Gestión de las Personas - DSSO"]]], 422);
        }

        $jefe_personal = $grupo->firmantes()->where('role_id', 4)->where('status', true)->first();
        if (!$jefe_personal) {
            return response(["errors" => ["observacion" => ["No registras Jefe de Personal. Contacte a Depto. Gestión de las Personas - DSSO"]]], 422);
        }

        $historys[] = [
            'type'              => HistoryActionUser::TYPE_3,
            'user_id'           => $user->id,
            'send_to_user_id'   => $jefe_personal->funcionario->id,
            'observacion'       => $request->observacion
        ];

        $add = $user->addHistorys($historys);

        if ($add) {
            $email = $jefe_personal->funcionario->email;
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Solicitud de cambio de datos enviada.",
                    'message'       => "Se envió a $email"
                )
            );
        }
    }
}
