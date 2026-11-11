<?php

namespace App\Services\Sirh;

use App\Models\Solicitud;
use App\Models\TipoComision;
use App\Jobs\ConsultarMovimientoSirh;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsultaPersistidaService
{
    private $service;
    public function __construct(SolicitudMovimientoService $service) { $this->service = $service; }

    private function tabla() { return DB::table('sirh_consultas'); }

    public function clave(array $filtros) { return hash('sha256', json_encode($filtros)); }

    public function adquirir($clave, $segundos)
    {
        $this->tabla()->insertOrIgnore(['clave' => $clave]);
        $token = (string) Str::uuid();
        $ok = $this->tabla()->where('clave', $clave)->where('bloqueado_hasta', '<=', time())
            ->update(['token' => $token, 'bloqueado_hasta' => time() + $segundos]);
        return $ok ? $token : null;
    }

    public function liberar($clave, $token)
    {
        $this->tabla()->where('clave', $clave)->where('token', $token)->update(['token' => null, 'bloqueado_hasta' => 0]);
    }

    public function obtener(Solicitud $solicitud, $encolar = true, $directo = false, $forzar = false)
    {
        try { $filtros = $this->service->filtros($solicitud); }
        catch (\InvalidArgumentException $e) {
            return ['estado' => 'datos_incompletos', 'mensaje' => $e->getMessage(), 'consultado_en' => null, 'filtros' => null, 'resumen' => null, 'registros' => [], 'pendiente' => false];
        }
        $clave = $this->clave($filtros);
        $row = $this->tabla()->where('clave', $clave)->first();
        $result = $row && $row->resultado ? json_decode($row->resultado, true) : [
            'estado' => 'sin_consultar', 'mensaje' => 'Sin consultar', 'consultado_en' => null,
            'filtros' => $filtros, 'resumen' => null, 'registros' => []
        ];
        if ($result['estado'] === 'sin_registros') $result['mensaje'] = 'Sin resultados';
        $result['metodo_consulta_nombre'] = TipoComision::METODOS_CONSULTA_SIRH[$filtros['metodo_consulta']];
        // Actualizar textos de resultados ya guardados sin volver a consultar SIRH.
        foreach ($result['registros'] as &$registro) {
            $codigo = $registro['clasificacion']['codigo'] ?? null;
            if (in_array($codigo, ['cubre_solicitud', 'solapamiento_parcial'], true)) $codigo = 'dentro_del_rango';
            if (isset(SolicitudMovimientoService::ETIQUETAS_FECHAS[$codigo])) {
                $registro['clasificacion']['codigo'] = $codigo;
                $registro['clasificacion']['tipo'] = SolicitudMovimientoService::ETIQUETAS_FECHAS[$codigo][1];
                $registro['clasificacion']['etiqueta'] = SolicitudMovimientoService::ETIQUETAS_FECHAS[$codigo][0];
            }
        }
        unset($registro);
        $vigente = $row && $row->vigente_hasta > time();
        $pending = $row && $row->bloqueado_hasta > time();
        if ((!$vigente || $forzar) && !$pending && ($encolar || $directo)) {
            $token = $this->adquirir($clave, 600);
            if ($token) {
                if ($forzar) {
                    $this->tabla()->where('clave', $clave)->where('token', $token)->update(['vigente_hasta' => 0]);
                    $vigente = false;
                }
                if ($directo) {
                    $updated = $this->ejecutar($solicitud->id, $clave, $token);
                    if (is_array($updated)) return array_merge($updated, ['pendiente' => false, 'vigente' => true]);
                }
                if (!$directo || $updated === null) {
                    try {
                        dispatch((new ConsultarMovimientoSirh($solicitud->id, $clave, $token))
                            ->onConnection('sirh_database')->onQueue('sirh'));
                        $pending = true;
                    } catch (\Exception $e) {
                        $this->liberar($clave, $token);
                        $result['mensaje'] = 'No fue posible programar la consulta SIRH';
                    }
                }
            } else $pending = true;
        }
        if ($vigente) $this->marcarCargaConfirmada($solicitud, $result);
        $solicitud->load_sirh = (bool) Solicitud::whereKey($solicitud->id)->value('load_sirh');
        $result['load_sirh'] = $solicitud->load_sirh;
        $result['pendiente'] = (bool) $pending;
        $result['vigente'] = (bool) $vigente;
        if ($pending && !$result['consultado_en']) {
            $result['estado'] = 'pendiente'; $result['mensaje'] = 'Consulta pendiente';
        }
        return $result;
    }

    // null: transporte ocupado; true: trabajo obsoleto; array: resultado guardado.
    public function ejecutar($solicitudId, $clave, $token)
    {
        $row = $this->tabla()->where('clave', $clave)->first();
        if (!$row || $row->token !== $token || $row->bloqueado_hasta <= time()) return true;
        $solicitud = Solicitud::with(['funcionario', 'tipoComision'])->find($solicitudId);
        try {
            if (!$solicitud || $this->clave($this->service->filtros($solicitud)) !== $clave) {
                $this->liberar($clave, $token); return true;
            }
        } catch (\InvalidArgumentException $e) { $this->liberar($clave, $token); return true; }
        $global = $this->adquirir('transporte', max(60, min(45, (int) config('sirh.sequence_timeout', 45)) + 30));
        if (!$global) return null;
        try {
            // La reserva puede haber esperado varios minutos en cola: renovarla antes del SOAP.
            $reserved = $this->tabla()->where('clave', $clave)->where('token', $token)
                ->where('bloqueado_hasta', '>', time())->update([
                    'bloqueado_hasta' => max((int) $row->bloqueado_hasta + 1, time() + max(600, (int) config('sirh.timeout') + 30))
                ]);
            if (!$reserved) return true;
            $result = $this->service->consultar($solicitud);
            $result['filtros']['codigo'] = $solicitud->codigo;
            $ttl = $result['estado'] === 'servicio_no_disponible' ? config('sirh.error_ttl', 300) : config('sirh.cache_ttl', 1800);
            DB::transaction(function () use ($clave, $token, $result, $ttl, $solicitud) {
                $guardado = $this->tabla()->where('clave', $clave)->where('token', $token)->update([
                    'resultado' => json_encode($result), 'vigente_hasta' => time() + max(1, (int) $ttl),
                    'token' => null, 'bloqueado_hasta' => 0
                ]);
                if ($guardado) $this->marcarCargaConfirmada($solicitud, $result);
            });
            return $result;
        } finally { $this->liberar('transporte', $global); }
    }

    private function marcarCargaConfirmada(Solicitud $solicitud, array $resultado)
    {
        if (($resultado['estado'] ?? null) !== 'con_registros' || empty($resultado['registros'])) return;

        // Actualización directa: no dispara recálculos del cometido ni crea movimientos manuales.
        // Si los datos cambiaron durante SOAP, el resultado anterior no debe marcar la solicitud.
        $query = DB::table($solicitud->getTable())->where('id', $solicitud->id);
        foreach (['user_id', 'tipo_comision_id', 'fecha_inicio', 'fecha_termino'] as $campo) {
            if (array_key_exists($campo, $solicitud->getAttributes())) {
                $query->where($campo, $solicitud->getRawOriginal($campo));
            }
        }
        $query->where(function ($query) {
            $query->where('load_sirh', false)->orWhereNull('load_sirh');
        })->update(['load_sirh' => true]);
    }

    public function actualizarListado(\Illuminate\Database\Eloquent\Collection $solicitudes)
    {
        $solicitudes->loadMissing(['funcionario', 'tipoComision']);
        foreach ($solicitudes as $solicitud) {
            // Solo los elementos de la página autorizada; nunca forzar ni ejecutar SOAP aquí.
            $resultado = $this->obtener($solicitud);
            $solicitud->setRelation('estadoSirh', $this->resumen($resultado));
        }
    }

    public function resumen(array $result)
    {
        $labels = ['con_registros' => 'Con registros en SIRH', 'sin_registros' => 'Sin resultados en SIRH',
            'sin_consultar' => 'Sin consultar', 'pendiente' => 'Consulta pendiente',
            'datos_incompletos' => 'Datos incompletos', 'servicio_no_disponible' => 'Error al consultar SIRH'];
        return ['estado' => $result['estado'], 'etiqueta' => $labels[$result['estado']] ?? 'Sin consultar',
            'mensaje' => $result['mensaje'], 'consultado_en' => $result['consultado_en'],
            'pendiente' => $result['pendiente'] ?? false, 'vigente' => $result['vigente'] ?? false,
            'cobertura_completa' => $result['resumen']['cobertura_completa_fechas'] ?? null];
    }
}
