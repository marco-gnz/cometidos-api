<?php

namespace App\Services;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FeriadosService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.sba_api.url');
    }

    public function obtenerFeriados($fecha)
    {
        $fecha      = Carbon::parse($fecha);
        $anio       = $fecha->format('Y');
        $cacheKey   = "feriados_{$anio}";
        $feriados   = Cache::get($cacheKey);
        if ($feriados !== null) {
            return $feriados;
        }

        try {
            $url        = "{$this->baseUrl}/feriados/{$anio}";
            $response   = Http::get($url);
            if ($response->successful()) {
                $feriados = $response->json()['data'] ?? [];

                if (is_array($feriados)) {
                    $fechas = collect($feriados)->pluck('fecha')->toArray();
                    Cache::put($cacheKey, $fechas, now()->addDays(31));
                    return $fechas;
                }
            }
            Log::error("Error API: {$response->status()} - " . json_encode($response->json()));
            return [];
        } catch (\Exception $exception) {
            Log::error("Error conexión API: {$exception->getMessage()}");
            $feriados = Cache::get($cacheKey);
            return $feriados !== null ? $feriados : [];
        }
    }
}
