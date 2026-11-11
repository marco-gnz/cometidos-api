<?php

return [
    'cache_ttl' => env('SIRH_CACHE_TTL', 1800),
    'error_ttl' => env('SIRH_ERROR_TTL', 300),
    'server_ip' => env('SIRH_SERVER_IP'),
    'endpoint' => 'http://' . env('SIRH_SERVER_IP') . '/wsconsultaausentismo/ConsultaAusentismoServicio.asmx',
    'acciones' => [\App\Models\TipoComision::METODO_CONSULTA_MOVIMIENTO => 'http://tempuri.org/Movimiento'],
    'usuario' => env('SIRH_USUARIO'),
    'clave' => env('SIRH_CLAVE'),
    'connect_timeout' => env('SIRH_CONNECT_TIMEOUT', 3),
    'timeout' => env('SIRH_TIMEOUT', 12),
    'sequence_timeout' => env('SIRH_SEQUENCE_TIMEOUT', 45),
];
