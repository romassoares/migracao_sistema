<?php

function normalize_and_fix_sql($sql, $codigoInfoideias)
{
    $table = '';
    // Normalize line breaks
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);

    // Fix CREATE TABLE as before
    $sql = preg_replace_callback(
        '/(CREATE\s+TABLE\s+[`"]?[\w\d_]+[`"]?\s*\()(.*?)(\)\s*ENGINE=.*?;)/is',
        function ($m) {
            $start = $m[1];
            $columns = trim($m[2]);
            $end = $m[3];
            if (stripos($columns, 'codigo_infoideias') === false) {
                $columns = rtrim($columns, ", \n\t\r");
                $columns .= ",\n  `codigo_infoideias` VARCHAR(20) DEFAULT NULL";
            }
            return $start . $columns . $end;
        },
        $sql
    );

    // Fix INSERT INTO
    $sql = preg_replace_callback(
        '/INSERT\s+INTO\s+[`"]?([\w\d_]+)[`"]?\s*\((.*?)\)\s*VALUES\s*(.*?)(?=(?:INSERT|CREATE|UPDATE|DELETE|$))/is',
        // '/INSERT\s+INTO\s+[`"]?([\w\d_]+)[`"]?\s*\((.*?)\)\s*VALUES\s*(.*)/is',
        function ($m) use ($codigoInfoideias) {
            $table = trim($m[1]);
            $cols = trim($m[2]);
            $values_block = RemoveStrangeCharacter(trim($m[3]));

            // Convert "" to NULL
            $values_block = preg_replace_callback(
                '/"((?:[^"\\\\]|\\\\.)*)"/s',
                function ($x) {
                    $val = str_replace('"', "'", $x[1]);
                    $val = str_replace('\\"', '"', $val);
                    return "'" . $val . "'";
                },
                $values_block
            );

            // Add the new column if missing
            if (stripos($cols, 'codigo_infoideias') === false) {
                $cols = rtrim($cols, ', ') . ', `codigo_infoideias`';
            }

            $new_values = [];
            $len = strlen($values_block);
            $buffer = '';
            $in_string = false;
            $string_char = '';
            $paren_count = 0;

            // Parse the values block character by character
            for ($i = 0; $i < $len; $i++) {
                $c = $values_block[$i];

                if ($in_string) { // Inside a string
                    $buffer .= $c;
                    if ($c === $string_char && $values_block[$i - 1] !== '\\') { // End of string
                        $in_string = false;
                    }
                } else {
                    // Not in a string
                    if ($c === '"' || $c === "'") {
                        $in_string = true;
                        $string_char = $c;
                        $buffer .= $c;
                    } elseif ($c === '(') { // Start of tuple
                        $paren_count++;
                        $buffer .= $c;
                    } elseif ($c === ')') { // End of tuple
                        $paren_count--;
                        $buffer .= $c;

                        if ($paren_count === 0) {
                            // End of tuple
                            $tuple = trim($buffer, " \n\t");
                            // Add new value before closing )
                            $tuple = rtrim($tuple, ')') . ", '" . addslashes($codigoInfoideias) . "')";
                            $new_values[] = $tuple;
                            $buffer = '';
                        }
                    } else {
                        $buffer .= $c;
                    }
                }
            }

            return "INSERT INTO `$table` ($cols) VALUES \n" . implode(",\n", $new_values) . ";";
        },
        $sql
    );
    // writeInFileLog($sql);
    // Remove apenas INSERTs vazios (sem valores)
    $sql = preg_replace('/INSERT\s+INTO\s+[`"\w]+[`"]?\s*\([^)]*\)\s*VALUES\s*;\s*/is', '', $sql);

    // Limpa sobras de formatação
    $sql = preg_replace([
        '/,\s*,+/',
        '/,\s*;/',
        '/\(\s*,/',
        '/,\s*\)/'
    ], [
        ',',
        ';',
        '(',
        ')'
    ], $sql);

    return ['sql' => $sql, 'table' => $table];
}



// function process_insert_block($insert, $codigo_infoideias)
// {
//     // Garante ponto e vírgula no final
//     if (substr(rtrim($insert), -1) !== ';') {
//         $insert = rtrim($insert, ',') . ';';
//     }

//     // Separa cabeçalho e valores
//     if (!preg_match('/^INSERT\s+INTO\s+[`"]?([\w\d_]+)[`"]?\s*(\([^)]+\))?\s*VALUES\s*(.+);$/is', $insert, $m)) {
//         return $insert; // retorna original se não casar
//     }

//     $table = $m[1];
//     $columnsRaw = isset($m[2]) ? trim($m[2]) : '';
//     $valuesRaw = trim($m[3]);

//     $hasColumns = $columnsRaw !== '';
//     if ($hasColumns) {
//         $columns = rtrim(ltrim($columnsRaw, '('), ')');
//         if (stripos($columns, 'codigo_infoideias') === false) {
//             $columns .= ',`codigo_infoideias`';
//         }
//     }

//     // Divide cada grupo de valores
//     $valuesGroups = [];
//     $level = 0;
//     $current = '';
//     $in_quotes = false;
//     $len = strlen($valuesRaw);

//     for ($i = 0; $i < $len; $i++) {
//         $ch = $valuesRaw[$i];
//         if ($ch === '"' && ($i == 0 || $valuesRaw[$i - 1] !== '\\')) {
//             $in_quotes = !$in_quotes;
//         }
//         if (!$in_quotes) {
//             if ($ch === '(') $level++;
//             if ($ch === ')') $level--;
//         }
//         $current .= $ch;
//         if ($level === 0 && !$in_quotes && trim($current) !== '') {
//             $valuesGroups[] = trim($current, ", ");
//             $current = '';
//         }
//     }

//     // Adiciona o codigo_infoideias em cada grupo
//     $newValues = [];
//     foreach ($valuesGroups as $group) {
//         $group = trim($group, '() ');
//         // substitui "" → NULL
//         $group = preg_replace('/""/', 'NULL', $group);
//         // converte "texto" para 'texto'
//         $group = preg_replace_callback('/"([^"]*)"/', function ($m) {
//             $val = trim($m[1]);
//             if ($val === '') return 'NULL';
//             $val = str_replace("'", "''", $val);
//             return "'$val'";
//         }, $group);
//         $group .= ",'$codigo_infoideias'";
//         $newValues[] = "($group)";
//     }

//     if ($hasColumns)
//         return "INSERT INTO `$table` ($columns) VALUES " . implode(",", $newValues) . ";";
//     else
//         return "INSERT INTO `$table` VALUES " . implode(",", $newValues) . ";";
// }



function trata_json_request($json)
{

    $data1 = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => false, 'msg' => 'Invalid JSON']);
        exit;
    }

    if (!is_array($data1)) {
        if (is_object($data1)) {
            $data1 = (array) $data1;
        } else {
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'Invalid data format']);
            exit;
        }
    }
    $request = [];

    foreach ($data1 as $key => $value) {
        if (is_array($value)) {
            $request[$key] = $value; // preserva arrays do JSON
            continue;
        }
        if (is_object($value)) {
            $request[$key] = (array)$value; // opcional: converte objetos aninhados
            continue;
        }
        $request[$key] = filter_var($value, FILTER_DEFAULT);
    }

    return (object) $request;
}

function validateRequest($data_post, $regras)
{
    $dados = [];
    $erros = '';

    foreach ($regras as $campo => $regra) {

        if ($regra['type'] == "email") {
            $valor = isset($data_post[$campo]) ? trim($data_post[$campo]) : '';
            if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                $erros .= "O campo '$campo' deve ser um e-mail válido.<br/>";
            }
            $dados[$campo] = trim($valor);
            // continue;
        }

        if ($regra['type'] == "string") {
            $valor = isset($data_post[$campo]) ? trim($data_post[$campo]) : '';
            $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
            $dados[$campo] = trim($valor);
            // continue;
        }

        if ($regra['type'] == "int") {
            $valor = isset($data_post[$campo]) ? trim($data_post[$campo]) : 0;
            if (!filter_var($valor, FILTER_VALIDATE_INT)) {
                $erros .= "O campo '$campo' deve ser um número inteiro.<br/>";
            }
            $dados[$campo] = (int)$valor;
            // continue;
        }

        if ($regra['type'] == "check") {
            // if ($campo == "obrigatorio") {
            //     var_dump($campo);
            //     var_dump($data_post);
            //     die;
            // }
            $valor = is_null($data_post[$campo]) ? '0' : '1';
            $dados[$campo] = $valor;
            // continue;
        }

        if ($regra['type'] == "array") {
            $valor = isset($data_post[$campo]) ? $data_post[$campo] : [];
            if (!is_array($valor)) {
                $erros .= "O campo '$campo' deve ser um array válido.<br/>";
            }
            $dados[$campo] = $valor;
            // continue;
        }

        if (!empty($regra['required']) && (empty($valor) || (is_array($valor) && count($valor) == 0))) {
            $erros .= "O campo '$campo' é obrigatório.<br/>";
            continue;
        }
    }

    return [
        'valido' => empty($erros),
        'dados' => $dados,
        'erros' => $erros,
    ];
}

function trata_group_concat($data, $campo)
{
    // GROUP_CONCAT(
    // 	CONCAT(
    //         'id_log_orulo_tipologia@@', IFNULL(logs.id_log_orulo_tipologia,''),
    //        '##data_alteracao@@', IFNULL(DATE_FORMAT(logs.data_alteracao, '%d/%m/%Y %H:%i:%s'),''), 
    //        )
    //        ORDER BY logs.data_alteracao DESC 
    //        SEPARATOR ' || '
    //    ) AS logs_alteracoes
    // $campo = logs_alteracoes
    $resultado = [];
    // dd($data);
    // foreach ($data as $row) {
    $resultArray = [];
    // if (!empty($row[$campo])) {
    $items = explode(' || ', $data[0]);
    foreach ($items as $item) {
        $item = trim($item);
        $keyValuePairs = explode('##', $item);
        $aux = [];
        foreach ($keyValuePairs as $pair) {
            if (strpos($pair, '@@') !== false) {
                list($key, $value) = explode('@@', $pair, 2);
                $aux[$key] = $value;
            }
        }
        $resultArray[] = $aux;
    }
    // }
    $row[$campo] = $resultArray;
    $resultado[] = $row;
    // }
    return $resultado[0][$campo];
}

function return_api($status = 404, $msg = '', $data = [])
{
    $statusRetorno = false;
    http_response_code($status);
    if ($status == 200)
        $statusRetorno = true;
    echo json_encode(['status' => $statusRetorno, 'data' => $data, 'msg' => $msg]);
    return;
}

function prepara_array_to_associate($array)
{
    $headers = [];
    $data = [];

    foreach ($array as $indexEl => $item) {
        $flattened = [];
        monta_array($item, $flattened);

        foreach ($flattened as $key => $value) {
            if (!in_array($key, $headers) && $indexEl == 0)
                $headers[] = $key;
        }

        $data[] = $flattened;
    }

    $output = [];
    $output[] = $headers;

    foreach ($data as $row) {
        $line = [];
        foreach ($headers as $header)
            $line[] = isset($row[$header]) ? $row[$header] : null;

        $output[] = $line;
    }
    $output = array_unique($output);
    return $output;
}

function monta_array($item, &$flattened, $prefix = '')
{
    foreach ((array)$item as $key => $value) {
        $fullKey = $prefix ? $prefix . '.' . $key : $key;

        if (is_string($value)) {
            $flattened[$fullKey] = (string) $value;
            continue;
        }

        if (is_array($value) || is_object($value)) {
            $temp = is_object($value) ? (array)$value : $value;
            if (count($temp) > 0) {
                monta_array($value, $flattened, $fullKey);
            } else {
                $flattened[$fullKey] = null;
            }
        }
    }
}


function refValues($arr)
{
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}


// ==========================================================
function monta_caminhos_absolutos_arquivo($items, $prefix = '')
{
    foreach ($items as $key => $value) {
        if (is_numeric($key)) {
            $currentPrefix = $prefix;
        } else {
            $currentPrefix = $prefix === '' ? $key : $prefix . '/' . $key;
        }

        if (is_array($value)) {
            yield from monta_caminhos_absolutos_arquivo($value, $currentPrefix);
        } else {
            yield $currentPrefix;
        }
    }
}

// function monta_caminhos_absolutos_arquivo($items, &$caminhos_absolutos, $prefix = '')
// {
//     foreach ($items as $key => $value) {
//         $segment = '';
//         if (is_numeric($key)) {
//             if (empty($prefix)) {
//                 $segment = '';
//             } else {
//                 $segment = $prefix;
//             }
//         } else {
//             if ($prefix === '') {
//                 $segment = $key;
//             } else {
//                 $segment = $prefix . '/' . $key;
//             }
//         }
//         $currentPrefix = $segment;

//         if (is_array($value)) {
//             monta_caminhos_absolutos_arquivo($value, $caminhos_absolutos, $currentPrefix);
//         } elseif (is_scalar($value) || is_null($value)) {
//             $caminhos_absolutos[] = $currentPrefix;
//         }
//     }
// }

function parseDateFlexible($value)
{
    // troca "/" por "-" para ajudar o DateTime a entender
    $normalized = str_replace('/', '-', trim($value));
    $value = str_replace('dataed_', '', $value);
    // $value = ltrim($value, '-');
    try {
        $date = new DateTime($normalized);
        return $date;
    } catch (Exception $e) {
        return false; // não conseguiu interpretar
    }
}

function limitaQtdLinhasPreview($data, $maxLinhas = 300)
{
    if (count($data) <= $maxLinhas) {
        return $data;
    }

    $result = [];
    $step = floor(count($data) / $maxLinhas);
    for ($i = 0; $i < count($data); $i += $step) {
        $result[] = $data[$i];
        if (count($result) >= $maxLinhas) {
            break;
        }
    }

    return $result;
}
