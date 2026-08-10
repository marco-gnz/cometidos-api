<?php

namespace App\Services;

use App\Models\Solicitud;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GecomService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.sba_api.url');
    }

    public function obtenerViatico(Solicitud $solicitud): ?array
    {
        if (!$solicitud->derecho_pago) {
            return null;
        }

        try {
            $response = Http::accept('application/vnd.api+json')
                ->timeout(15)
                ->retry(2, 300)
                ->get("{$this->baseUrl}/viaticos", [
                    'filter' => [
                        'n_resolucion'      => $solicitud->codigo,
                        'n_resolucion_sirh' => $solicitud->nResolucionSirh(),
                        'rut_completo'      => $solicitud->funcionario->rut_completo,
                        'fecha_inicio'      => Carbon::parse($solicitud->fecha_inicio)
                            ->toDateString(),
                        'fecha_termino'     => Carbon::parse($solicitud->fecha_termino)
                            ->toDateString(),
                    ],
                ]);

            if ($response->successful()) {
                return data_get($response->json(), 'data.attributes');
            }

            if ($response->status() === 404) {
                return null;
            }

            Log::warning('La API de viáticos respondió con error.', [
                'status' => $response->status(),
                'errors' => data_get($response->json(), 'errors', []),
            ]);

            return null;
        } catch (\Throwable $error) {
            Log::error('Error de conexión con la API de viáticos.', [
                'message'       => $error->getMessage(),
                'solicitud_id'  => $solicitud->id,
                'n_resolucion'  => $solicitud->codigo,
            ]);

            return null;
        }
    }
}
