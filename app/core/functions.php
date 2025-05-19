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
    $erros = [];

    // dd($data_post, $regras);

    foreach ($regras as $campo => $regra) {

        if (isset($data_post[$campo]) && !is_array($data_post[$campo])) {
            $valor = isset($data_post[$campo]) ? trim($data_post[$campo]) : '';
        } else {
            $valor = isset($data_post[$campo]) ? $data_post[$campo] : [];
        }


        if (!empty($regra['required']) && $valor === '' && $regra['type'] !== 'check') {
            $erros[$campo] = "O campo '$campo' é obrigatório.";
            continue;
        }

        if ($valor === '') {
            $dados[$campo] = null;
            continue;
        }

        // if ($regra['type'] !== 'array')
        //     $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');

        switch ($regra['type']) {
            case 'int':
                if (!filter_var($valor, FILTER_VALIDATE_INT)) {
                    $erros[$campo] = "O campo '$campo' deve ser um número inteiro.";
                    continue 2;
                }
                $dados[$campo] = (int)$valor;
                break;

            case 'email':
                if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $erros[$campo] = "O campo '$campo' deve ser um e-mail válido.";
                    continue 2;
                }
                $dados[$campo] = $valor;
                break;

            case 'check':
                if (isset($data_post[$campo])) {
                    $dados[$campo] = '1';
                } else {
                    $dados[$campo] = '0';
                }
                break;

            case 'array':
                if (!is_array($valor)) {
                    $erros[$campo] = "O campo '$campo' deve ser um array válido.";
                    continue 2;
                }
                $dados[$campo] = $valor;
                break;

            case 'string':
            default:
                $dados[$campo] = $valor;
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
