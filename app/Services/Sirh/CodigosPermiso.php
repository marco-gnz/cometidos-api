<?php

namespace App\Services\Sirh;

use InvalidArgumentException;

class CodigosPermiso
{
    public static function normalizar($valor)
    {
        if ($valor === null || $valor === '') return [];
        if (!is_string($valor) && !is_int($valor)) throw new InvalidArgumentException('Ingrese códigos numéricos separados por punto y coma.');
        $valor = trim((string) $valor);
        if ($valor === '') return [];
        if (strlen($valor) > 255 || !preg_match('/^[0-9]+(?:\s*;\s*[0-9]+)*\s*;?$/D', $valor)) {
            throw new InvalidArgumentException('Ingrese códigos numéricos separados por punto y coma (máximo 255 caracteres).');
        }
        $codigos = [];
        foreach (explode(';', rtrim($valor, "; \t\r\n")) as $codigo) {
            $codigo = ltrim(trim($codigo), '0');
            $codigo = $codigo === '' ? '0' : $codigo;
            if (strlen($codigo) > 10 || (strlen($codigo) === 10 && strcmp($codigo, '2147483647') > 0)) {
                throw new InvalidArgumentException('Los códigos deben ser enteros entre 0 y 2147483647.');
            }
            if (!in_array($codigo, $codigos, true)) $codigos[] = $codigo;
        }
        if (in_array('0', $codigos, true) && count($codigos) > 1) {
            throw new InvalidArgumentException('El código 0 consulta todos los tipos y debe ingresarse solo.');
        }
        return $codigos;
    }
}
