<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\HistoryActionUser;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['status' => __($status)]);
    }

    public function changePass(ChangePasswordRequest $request, $uuid)
    {
        try {
            if (Hash::check($request->password, $request->user()->password)) {

                $usuario = User::where('uuid', $uuid)->firstOrFail();
                $old_pass = $usuario->password;
                $usuario->password = Hash::make($request->new_password);
                $update = $usuario->save();

                $usuario = $usuario->fresh();
                $new_pass = $usuario->password;

                if ($update) {
                    //enviar email
                    //almacenar en history user
                    $historys[] = [
                        'type'      => HistoryActionUser::TYPE_0,
                        'user_id'   => $usuario->id,
                        'data_old'  => $old_pass,
                        'data_new'  => $new_pass
                    ];

                    $usuario->addHistorys($historys);
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Contraseña modificada con éxito.",
                            'message'       => null,
                        )
                    );
                }
            } else {
                return response(["errors" => ["password" => ["La contraseña actual es inconrrecta"]]], 422);
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
