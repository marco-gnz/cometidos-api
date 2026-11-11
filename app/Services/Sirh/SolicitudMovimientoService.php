<?php

namespace App\Services\Sirh;

use App\Models\Solicitud;
use App\Models\TipoComision;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class SolicitudMovimientoService
{
    const ETIQUETAS_FECHAS = [
        'coincide' => ['Exactas', 'success'],
        'dentro_del_rango' => ['Dentro del rango', 'warning'],
        'fuera_del_rango' => ['Fuera del rango', 'danger'],
        'fechas_invalidas' => ['Fechas inválidas', 'danger'],
    ];
    private $client;
    public function __construct(MovimientoClient $client)
    {
        $this->client = $client;
    }

    public static function date($value, $format)
    {
        if (!is_string($value) || $value === '') return null;
        try {
            $date = CarbonImmutable::createFromFormat('!' . $format, $value);
            return $date && $date->format($format) === $value ? $date : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function filtros(Solicitud $solicitud)
    {
        $codigo = $solicitud->tipoComision ? $solicitud->tipoComision->cod_web_service : null;
        if ($codigo === null || trim((string) $codigo) === '') throw new InvalidArgumentException('Tipo de comisión sin código de consulta SIRH');
        $metodo = $solicitud->tipoComision->metodo_consulta;
        if (!isset(TipoComision::METODOS_CONSULTA_SIRH[$metodo])) {
            throw new InvalidArgumentException('Tipo de comisión sin método de consulta SIRH válido');
        }
        $rut = $solicitud->funcionario ? strtoupper(str_replace('.', '', trim($solicitud->funcionario->rut_completo))) : '';
        if (!preg_match('/^[0-9]{1,8}-[0-9K]$/', $rut)) throw new InvalidArgumentException('Funcionario de origen sin RUT válido');
        list($numero, $dv) = explode('-', $rut);
        $sum = 0;
        $factor = 2;
        foreach (str_split(strrev($numero)) as $digit) {
            $sum += (int) $digit * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $check = 11 - ($sum % 11);
        if (($check === 11 ? '0' : ($check === 10 ? 'K' : (string) $check)) !== $dv) throw new InvalidArgumentException('Funcionario de origen sin RUT válido');
        $attributes = $solicitud->getAttributes();
        $inicio = self::date(substr((string) ($attributes['fecha_inicio'] ?? ''), 0, 10), 'Y-m-d');
        $fin = self::date(substr((string) ($attributes['fecha_termino'] ?? ''), 0, 10), 'Y-m-d');
        if (!$inicio || !$fin || $inicio->gt($fin)) throw new InvalidArgumentException('Período del cometido ausente o inválido');
        $codigo = implode(';', CodigosPermiso::normalizar($codigo));
        return ['metodo_consulta' => (int) $metodo, 'RUT' => $rut, 'fechaDesde' => $inicio->format('d/m/Y'), 'fechaHasta' => $fin->format('d/m/Y'), 'tipoPermiso' => (string) $codigo];
    }

    public function consultar(Solicitud $solicitud)
    {
        $result = ['estado' => 'datos_incompletos', 'mensaje' => '', 'consultado_en' => now()->toIso8601String(), 'filtros' => null, 'resumen' => null, 'registros' => []];
        try {
            $result['filtros'] = $this->filtros($solicitud);
            $result['metodo_consulta_nombre'] = TipoComision::METODOS_CONSULTA_SIRH[$result['filtros']['metodo_consulta']];
        } catch (InvalidArgumentException $e) {
            $result['mensaje'] = $e->getMessage();
            return $result;
        }
        try {
            $records = [];
            $result['filtros']['tipos_consultados'] = [];
            $result['filtros']['tipoPermiso_encontrado'] = null;
            // Limitar la secuencia completa para no superar el timeout de 60 s del trabajador.
            $limite = microtime(true) + max(0.1, min(45, (float) config('sirh.sequence_timeout', 45)));
            foreach (explode(';', $result['filtros']['tipoPermiso']) as $codigo) {
                $restante = $limite - microtime(true);
                if ($restante <= 0) throw new \RuntimeException('Tiempo de consulta agotado');
                $consulta = array_diff_key($result['filtros'], array_flip(['tipos_consultados', 'tipoPermiso_encontrado']));
                $consulta['tipoPermiso'] = $codigo;
                $result['filtros']['tipos_consultados'][] = $codigo;
                $records = $this->client->consultar($consulta, $restante);
                if (count($records)) {
                    $result['filtros']['tipoPermiso_encontrado'] = $codigo;
                    break;
                }
            }
            $evaluation = $this->evaluar($records, $result['filtros']['fechaDesde'], $result['filtros']['fechaHasta']);
            $result = array_merge($result, $evaluation);
            $result['estado'] = count($records) ? 'con_registros' : 'sin_registros';
            $result['mensaje'] = count($records) ? 'Consulta realizada' : 'Sin resultados';
        } catch (\Exception $e) {
            $result['estado'] = 'servicio_no_disponible';
            $result['mensaje'] = 'No fue posible consultar SIRH. Intente nuevamente.';
        }
        return $result;
    }

    public function evaluar(array $records, $desde, $hasta)
    {
        $start = self::date($desde, 'd/m/Y');
        $end = self::date($hasta, 'd/m/Y');
        $intervals = [];
        $invalid = 0;
        $exact = false;
        $labels = self::ETIQUETAS_FECHAS;
        foreach ($records as &$record) {
            $a = self::date($record['Fecha_de_inicio'] ?? null, 'd/m/Y');
            $b = self::date($record['Fecha_de_termino'] ?? null, 'd/m/Y');
            $code = 'fechas_invalidas';
            $days = 0;
            $covers = false;
            if (!$a || !$b || $a->gt($b)) {
                $invalid++;
            } else {
                $covers = $a->lte($start) && $b->gte($end);
                if ($a->eq($start) && $b->eq($end)) {
                    $code = 'coincide';
                    $exact = true;
                } elseif ($b->lt($start) || $a->gt($end)) $code = 'fuera_del_rango';
                else $code = 'dentro_del_rango';
                $left = $a->max($start);
                $right = $b->min($end);
                if ($left->lte($right)) {
                    $days = $left->diffInDays($right) + 1;
                    $intervals[] = [$left, $right];
                }
            }
            $record['inicio'] = $a ? $a->format('Y-m-d') : null;
            $record['termino'] = $b ? $b->format('Y-m-d') : null;
            $record['inicio_legible'] = $a ? $a->format('d-m-Y') : null;
            $record['termino_legible'] = $b ? $b->format('d-m-Y') : null;
            $record['clasificacion'] = ['codigo' => $code, 'etiqueta' => $labels[$code][0], 'tipo' => $labels[$code][1]];
            $record['dias_interseccion'] = $days;
            $record['cubre_solicitud'] = $covers;
        }
        unset($record);
        usort($intervals, function ($a, $b) {
            return $a[0]->getTimestamp() <=> $b[0]->getTimestamp();
        });
        $merged = [];
        foreach ($intervals as $interval) {
            $last = count($merged) - 1;
            if ($last >= 0 && $interval[0]->lte($merged[$last][1]->addDay())) $merged[$last][1] = $merged[$last][1]->max($interval[1]);
            else $merged[] = $interval;
        }
        $covered = 0;
        foreach ($merged as $interval) $covered += $interval[0]->diffInDays($interval[1]) + 1;
        $total = $start->diffInDays($end) + 1;
        return ['registros' => $records, 'resumen' => ['cantidad_registros' => count($records), 'fechas_invalidas' => $invalid, 'coincidencia_exacta' => $exact, 'cobertura_completa_fechas' => $covered === $total, 'dias_cubiertos' => $covered, 'dias_no_cubiertos' => $total - $covered, 'alcance' => 'Cobertura exclusivamente de fechas; no certifica jornada, horas, contrato ni corrección administrativa.']];
    }
}
