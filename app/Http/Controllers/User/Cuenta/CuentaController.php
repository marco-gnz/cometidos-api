<?php

namespace App\Http\Controllers\User\Cuenta;

use App\Events\ChangeDataSolicitud;
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

        $contrato = $user->contratos()
            ->orderBy('id', 'DESC')
            ->first();

        if ($contrato && !$contrato->grupo) {
            return response(["errors" => ["observacion" => ["No registras grupo de firmante. Contacte a Depto. Gestión de las Personas - DSSO"]]], 422);
        }

        $jefe_personal = $contrato->grupo->firmantes()->where('role_id', 4)->where('status', true)->first();
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
            $history = $user->historys()->where('type', HistoryActionUser::TYPE_3)->orderBy('id', 'DESC')->first();
            ChangeDataSolicitud::dispatch($history);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Solicitud de cambio de datos enviada.",
                    'message'       => null
                )
            );
        }
    }
}
