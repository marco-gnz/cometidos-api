<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        /* $this->renderable(function (Throwable $e, $request) {
            $isMaintenance = Cache::get('app_is_maintenance', null);

            if ($isMaintenance === null) {
                $isMaintenance = app()->isDownForMaintenance();
                Cache::put('app_is_maintenance', $isMaintenance, 60); // Mantén el valor por 60 minutos
            }

            if ($isMaintenance && ($request->expectsJson() || $request->is('api/*'))) {
                return response()->json([
                    'message'       => 'La aplicación está en mantenimiento.',
                    'maintenance'   => true,
                ], 503);
            }

            return parent::render($request, $e);
        }); */
    }
}
