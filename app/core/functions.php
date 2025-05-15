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

    foreach ($regras as $campo => $regra) {
        $valor = isset($data_post[$campo]) ? trim($data_post[$campo]) : '';

        if (!empty($regra['required']) && $valor === '') {
            $erros[$campo] = "O campo '$campo' é obrigatório.";
            continue;
        }

        if ($valor === '') {
            $dados[$campo] = null;
            continue;
        }

        $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');

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
