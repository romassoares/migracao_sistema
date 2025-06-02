<?php
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
            // dd($data_post[$campo]);
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


        // if ($regra['type'] !== 'array')
        //     $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');


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




function removeEmojis($text)
{
    $corrigido = preg_replace('/[\x{1F600}-\x{1F64F}]|' .   // Emojis padrão
        '[\x{1F300}-\x{1F5FF}]|' .   // Símbolos e pictogramas
        '[\x{1F680}-\x{1F6FF}]|' .   // Transporte e mapas
        '[\x{2600}-\x{26FF}]|' .     // Símbolos diversos
        '[\x{2700}-\x{27BF}]|' .     // Dingbats
        '[\x{1F1E6}-\x{1F1FF}]|' .   // Bandeiras
        '[\x{1F900}-\x{1F9FF}]|' .   // Suplementos (novos emojis)
        '[\x{1FA70}-\x{1FAFF}]|' .   // Suplemento adicional
        '[\x{1F018}-\x{1F270}]|' .   // Diversos
        '[\x{238C}-\x{2454}]|' .     // Símbolos técnicos
        '[\x{20D0}-\x{20FF}]/u', '', $text);
    return $corrigido;
}

// ---------------------------------------------------------------------------------
function convertToUtf8($string)
{
    if (empty($string)) return $string;

    $detectedCharset = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII', 'UCS-2'], true);

    $string = mb_convert_encoding($string, "UTF-8", $detectedCharset);


    $string = RemoveStrangeCharacter($string);


    return $string;
}

function RemoveStrangeCharacter($string)
{
    $replacePairs = ['‡Ã' => 'O', 'Ã' => 'Í', 'Ã“' => 'Ó', 'Ã‡' => 'Ç', 'Ã”' => 'Ô', 'Ã‰' => 'É', 'Ãƒ' => 'Ã', 'ÃŠ' => 'Ê', 'Ã€' => 'À', 'Ã•' => 'Õ', 'Ãš' => 'Ú', 'Ã›' => 'Û', 'Ãœ' => 'Ü', 'Ã„' => 'Ä', 'Ã‹' => 'Ë', 'ÃŒ' => 'Ì', 'ÃŽ' => 'Î', 'Ã¯' => 'Ï', 'Ã³' => 'ó', 'Ã§' => 'ç', 'Ã¢' => 'â', 'Ãª' => 'ê', 'Ã¡' => 'á', 'Ã©' => 'é', 'Ã´' => 'ô', 'Ã£' => 'ã', 'Ãº' => 'ú', 'Ã¹' => 'ù', 'Ã»' => 'û', 'Ã¼' => 'ü', 'Ã¤' => 'ä', 'Ã«' => 'ë', 'Ã®' => 'î', 'Ã¬' => 'ì', 'Â²' => '²', 'Ã ' => 'à', 'â€“' => '–', 'â€”' => '—', 'â€˜' => '‘', 'â€™' => '’', 'â€œ' => '“', 'â€' => '”', 'â€¢' => '•', 'â€¦' => '…', 'â€' => '†', 'Â©' => '©', 'Â®' => '®', 'Â±' => '±', 'Âµ' => 'µ', 'Â¥' => '¥', 'Â§' => '§', 'Â«' => '«', 'Â»' => '»', 'Â°' => '°', 'Â¶' => '¶', 'Ã½' => 'ý', 'Ã¿' => 'ÿ', 'Ã–' => 'Ö', 'ÃŸ' => 'ß', 'Ã†' => 'Æ', 'Ã˜' => 'Ø', 'Ã…' => 'Å', 'Ã²' => 'ò', 'Ã­' => 'í', 'Â¾' => '¾', 'Â½' => '½', 'Â¼' => '¼', 'Â¢' => '¢', 'Â£' => '£', 'Â¤' => '¤', 'Â¬' => '¬', 'â‚¬' => '€', 'â„¢' => '™'];
    $search = array_keys($replacePairs);
    $replace = array_values($replacePairs);
    $string = mb_str_replace($search, $replace, $string);

    $string = str_replace("•", " - ", $string);
    $string = str_replace("\u2060", " ", $string);
    $string = preg_replace('/\x{2060}/u', '', $string);
    $string = preg_replace('/\x{0303}/u', '', $string);
    $string = str_replace("\xCC\x83", " ", $string);
    $string = str_replace("➊", "1- ", $string);
    $string = str_replace("➋", "2- ", $string);
    $string = str_replace("➌", "3- ", $string);
    $string = str_replace("✦", "* ", $string);
    $string = str_replace("✔", " - ", $string);
    $string = str_replace("⩗", " - ", $string);
    $string = str_replace("✅", " - ", $string);
    $string = str_replace("2️⃣", " - ", $string);
    $string = str_replace("1️⃣", " - ", $string);
    $string = str_replace("⛱️", " ", $string);

    if (class_exists('Normalizer')) {
        $string = Normalizer::normalize($string, Normalizer::FORM_C);
    }
    $string = preg_replace('/\x{FE0F}/u', '', $string);
    return $string;
}

function mb_str_replace($search, $replace, $subject)
{
    foreach ($search as $key => $value) {
        $count = 0;
        $subject = mb_str_replace_helper($search[$key], $replace[$key], $subject, $count);
    }
    return $subject;
}

function mb_str_replace_helper($search, $replace, $subject, &$count)
{
    $searchLen = mb_strlen($search);
    while (($position = mb_stripos($subject, $search)) !== false) {
        $subject = mb_substr($subject, 0, $position) . $replace . mb_substr($subject, $position + $searchLen);
        $count++;
    }
    return $subject;
}


function refValues($arr)
{
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}
