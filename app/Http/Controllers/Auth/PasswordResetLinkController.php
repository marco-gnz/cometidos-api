<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(PasswordResetLinkRequest $request)
    {
        try {
            // Validación del campo 'rut'
            $request->validate([
                'rut' => ['required'],
            ]);

            // Encontrar al usuario por su RUT
            $user = User::where('rut_completo', $request->rut)->first();

            if (!$user) {
                return response()->json([
                    'errors' => [
                        'rut'  => ['RUT no existe en el sistema.']
                    ]
                ], 422);
            }

            $status = Password::sendResetLink([
                'email' => $user->email
            ]);
            if ($status != Password::RESET_LINK_SENT) {
                throw ValidationException::withMessages([
                    'email' => [__($status)],
                ]);
            }
            $new_message = "Te enviamos un correo a $user->email, ingresa y sigue el enlace del correo para configurar tu nueva contraseña.";
            return response()->json([
                'status'                => 'success',
                'title'                 => "¡Correo enviado con éxito!",
                'message'               => $new_message,
                'is_send_email'         => true
            ]);
        } catch (\Exception $error) {
            Log::error($error->getMessage());
            return response()->json(['error' => 'Error al recuperar contraseña'], 500);
        }
    }
}
