<?php

/**
 * Escreve valor na planilha por coluna/linha (1-based).
 */
function setCellValueByColumnAndRow($sheet, $colIndex, $rowIndex, $value)
{
    $columnLetter = columnLetter($colIndex);
    $cell = $columnLetter . $rowIndex;
    $sheet->setCellValue($cell, $value);
}

/**
 * Converte índice 1-based para letra de coluna do Excel.
 */
function columnLetter($colIndex)
{
    $dividend = $colIndex;
    $columnName = '';
    while ($dividend > 0) {
        $modulo = ($dividend - 1) % 26;
        $columnName = chr(65 + $modulo) . $columnName;
        $dividend = (int)(($dividend - $modulo) / 26);
    }
    return $columnName;
}

/**
 * Lista sequencial "solta": aceita chaves 0..n-1 como int OU string ("0","1","2"...)
 */
function isSequentialListLoose($arr): bool
{
    if (!is_array($arr)) return false;
    $i = 0;
    foreach ($arr as $k => $_) {
        // aceita 0 e "0" como o mesmo índice
        if ((string)$k !== (string)$i) return false;
        $i++;
    }
    return true;
}

/**
 * Extrai valores aninhados seguindo uma lista de chaves (ex.: ['fotos','url']).
 * Expande corretamente listas, inclusive quando as chaves são "0","1","2" (strings).
 */
function getNestedValues($data, $keys)
{
    $results = [];

    if (!is_array($keys)) {
        $keys = explode('.', $keys); // suporta "fotos.url"
    }

    // Permite acessar arrays e objetos (stdClass)
    $get = function ($container, $key) {
        if (is_array($container) && (array_key_exists($key, $container) || array_key_exists((string)$key, $container))) {
            return $container[$key] ?? $container[(string)$key] ?? null;
        }
        if (is_object($container) && isset($container->$key)) {
            return $container->$key;
        }
        return null;
    };

    $key = array_shift($keys);
    $value = $get($data, $key);

    if ($value === null) {
        return $results; // vazio
    }

    // Último nível
    if (empty($keys)) {
        if (is_array($value)) {
            if (isSequentialListLoose($value)) {
                foreach ($value as $v) {
                    $results[] = (is_array($v) || is_object($v))
                        ? json_encode($v, JSON_UNESCAPED_UNICODE)
                        : $v; // string/numero puro
                }
            } else {
                $results[] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $results[] = $value;
        }
        return $results;
    }

    // Ainda há níveis
    if (is_array($value)) {
        if (isSequentialListLoose($value)) {
            // lista → acumula na ordem (mantém alinhamento por índice)
            foreach ($value as $v) {
                $results = array_merge($results, getNestedValues($v, $keys));
            }
        } else {
            // objeto associativo → desce normal
            $results = array_merge($results, getNestedValues($value, $keys));
        }
    } elseif (is_object($value)) {
        $results = array_merge($results, getNestedValues($value, $keys));
    }

    return $results;
}

/**
 * Classifica o retorno de getNestedValues como escalar ou lista.
 * - Remove vazios ('', null, []) do fim
 * - Trata ['X', '', ''] como ESCALAR 'X'
 * - Se todos os não-vazios forem iguais => ESCALAR
 * - Se houver 2+ valores não-vazios distintos => LISTA
 * Retorna: ['type'=>'scalar','value'=>string] ou ['type'=>'list','values'=>array]
 */
function classifyValues(array $vals): array
{
    // Normaliza índices e remove vazios internos triviais ('' e null)
    $vals = array_values($vals);
    $filtered = array_values(array_filter($vals, static function ($v) {
        return !($v === '' || $v === null);
    }));

    // Nada significativo encontrado
    if (count($filtered) === 0) {
        return ['type' => 'scalar', 'value' => ''];
    }

    // Se só 1 valor não-vazio => escalar
    if (count($filtered) === 1) {
        $v = $filtered[0];
        if (is_array($v) || is_object($v)) {
            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        } elseif (!is_string($v)) {
            $v = (string)$v;
        }
        return ['type' => 'scalar', 'value' => $v];
    }

    // Se todos os não-vazios são iguais => escalar
    $asStrings = array_map(static function ($v) {
        if (is_array($v) || is_object($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        return (string)$v;
    }, $filtered);
    if (count(array_unique($asStrings)) === 1) {
        return ['type' => 'scalar', 'value' => $asStrings[0]];
    }

    // Caso contrário é lista real (mantém simples como string; objetos como JSON)
    $list = array_map(static function ($v) {
        if (is_array($v) || is_object($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        return (string)$v;
    }, $filtered);

    return ['type' => 'list', 'values' => array_values($list)];
}
