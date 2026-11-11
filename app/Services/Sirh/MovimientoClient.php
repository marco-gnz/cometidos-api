<?php

namespace App\Services\Sirh;

use App\Models\TipoComision;
use GuzzleHttp\Client;
use RuntimeException;

class MovimientoClient
{
    private $http;

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    public function consultar(array $filtros, $tiempoRestante = null)
    {
        if (!config('sirh.usuario') || !config('sirh.clave') || !config('sirh.server_ip')) {
            throw new RuntimeException('Servicio no configurado');
        }
        try {
            $metodo = $filtros['metodo_consulta'] ?? null;
            $this->nombreMetodo($metodo);
            if (!config('sirh.acciones.'.$metodo)) throw new RuntimeException('Acción SOAP no configurada');
            $timeout = max(0.1, (float) config('sirh.timeout'));
            if ($tiempoRestante !== null) $timeout = min($timeout, max(0.001, (float) $tiempoRestante));
            $response = $this->http->post(config('sirh.endpoint'), [
                'headers' => ['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => '"'.config('sirh.acciones.'.$metodo).'"'],
                'body' => $this->envelope($filtros),
                'connect_timeout' => min($timeout, max(0.1, (float) config('sirh.connect_timeout'))),
                'timeout' => $timeout,
                'allow_redirects' => false,
                'http_errors' => false,
            ]);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new RuntimeException('HTTP inválido');
            }
            return $this->parse((string) $response->getBody(), $metodo);
        } catch (\Exception $e) {
            // No propagar mensajes de transporte que pueden contener credenciales o datos personales.
            throw new RuntimeException('No fue posible consultar el servicio SIRH');
        }
    }

    private function nombreMetodo($metodo)
    {
        if (!isset(TipoComision::METODOS_CONSULTA_SIRH[$metodo])) throw new RuntimeException('Método SIRH inválido');
        return TipoComision::METODOS_CONSULTA_SIRH[$metodo];
    }

    public function envelope(array $filtros)
    {
        $nombre = $this->nombreMetodo($filtros['metodo_consulta'] ?? null);
        $doc = new \DOMDocument('1.0', 'utf-8');
        $env = $doc->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Envelope');
        $doc->appendChild($env);
        $body = $env->appendChild($doc->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Body'));
        $method = $body->appendChild($doc->createElementNS('http://tempuri.org/', $nombre));
        foreach (array_merge(['USUARIO' => config('sirh.usuario'), 'CLAVE' => config('sirh.clave')], array_intersect_key($filtros, array_flip(['RUT', 'fechaDesde', 'fechaHasta', 'tipoPermiso']))) as $key => $value) {
            $node = $method->appendChild($doc->createElementNS('http://tempuri.org/', $key));
            $node->appendChild($doc->createTextNode((string) $value));
        }
        return $doc->saveXML();
    }

    private function xml($xml)
    {
        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            throw new RuntimeException('XML no permitido');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $doc = new \DOMDocument();
            if (!$doc->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException('XML inválido');
            }
            return $doc;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function parse($xml, $metodo = TipoComision::METODO_CONSULTA_MOVIMIENTO)
    {
        $nombre = $this->nombreMetodo($metodo);
        $doc = $this->xml($xml);
        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
        if ($xp->query('/s:Envelope/s:Body/s:Fault')->length) {
            throw new RuntimeException('SOAP Fault');
        }
        $responses = $xp->query('/s:Envelope/s:Body/*[local-name()="'.$nombre.'Response" and namespace-uri()="http://tempuri.org/"]');
        if ($responses->length !== 1 || $xp->query('/s:Envelope/s:Body/*')->length !== 1) {
            throw new RuntimeException('Respuesta desconocida');
        }
        $response = $responses->item(0);
        // Variante real sin resultados: MovimientoResponse vacío,
        // sin Result. No interpretar texto de error ni elementos desconocidos como vacío.
        if ($xp->query('./*', $response)->length === 0 && trim($response->textContent) === '') return [];
        $results = $xp->query('./*[local-name()="'.$nombre.'Result"]', $response);
        if ($results->length !== 1) {
            throw new RuntimeException('Respuesta desconocida');
        }
        $result = $results->item(0);
        if (!$result->getElementsByTagName('respuesta')->length && trim($result->textContent) !== '') {
            $doc = $this->xml(trim($result->textContent));
            $xp = new \DOMXPath($doc);
            $roots = $xp->query('/*[local-name()="respuesta"]');
        } else {
            $roots = $xp->query('./*[local-name()="respuesta"]', $result);
        }
        if ($roots->length !== 1) {
            throw new RuntimeException('Respuesta desconocida');
        }
        $root = $roots->item(0);
        foreach ($xp->query('./*[local-name()="Estado"]', $root) as $estado) {
            $codes = $xp->query('./*[local-name()="Codigo"]', $estado);
            if ($codes->length !== 1 || trim($codes->item(0)->textContent) !== '0') {
                throw new RuntimeException('Error funcional');
            }
        }
        $records = [];
        $fields = ['Tipo_Permiso', 'Descripcion_permiso', 'Fecha_de_inicio', 'Fecha_de_termino', 'Dias_permiso', 'Jornada', 'Dias_del_periodo', 'Codigo_servicio', 'Servicio', 'Codigo_establecimiento', 'Establecimiento', 'Tipo_contrato', 'Correlativo', 'Codigo_ley', 'Ley_afecto', 'Codigo_calidad_juridica', 'Calidad_juridica'];
        foreach ($root->childNodes as $child) {
            if (!($child instanceof \DOMElement)) continue;
            if ($child->localName === 'Estado') continue;
            if ($child->localName !== strtolower($nombre)) throw new RuntimeException('Respuesta desconocida');
            $record = array_fill_keys($fields, null);
            $known = false;
            foreach ($child->childNodes as $field) {
                if ($field instanceof \DOMElement && in_array($field->localName, $fields, true)) {
                    $known = true;
                    $record[$field->localName] = trim($field->textContent);
                }
            }
            if (!$known) throw new RuntimeException('Registro desconocido');
            $records[] = $record;
        }
        if (!$records && trim($root->textContent) !== '' && !$xp->query('./*[local-name()="Estado"]', $root)->length) {
            throw new RuntimeException('Respuesta desconocida');
        }
        return $records;
    }
}
