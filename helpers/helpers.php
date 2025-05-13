<?php

date_default_timezone_set('America/Sao_Paulo');


function dd(...$array)
{
    echo '<pre style="background-color: #2c2c2c; color: #ccff33; font-size: 16px; padding: 2px; word-wrap: break-word; white-space: pre-wrap; width: auto;">';
    foreach ($array as $key => $value) {
        var_dump($value);
    }
    echo '</pre>';
    die();
}

function notdie(...$array)
{
    echo '<pre style="background-color: #2c2c2c; color: #ccff33; font-size: 16px; padding: 2px; word-wrap: break-word; white-space: pre-wrap; width: auto;">';
    foreach ($array as $key => $value) {
        var_dump($value);
    }
    echo '</pre>';
}

function decryptData($encryptedData, $password)
{
    $key = hash('sha256', $password, true);

    $encryptedDataWithIV = base64_decode($encryptedData);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($encryptedDataWithIV, 0, $ivLength);
    $encryptedDataWithoutIV = substr($encryptedDataWithIV, $ivLength);

    $decryptedData = openssl_decrypt($encryptedDataWithoutIV, 'aes-256-cbc', $key, 0, $iv);

    return $decryptedData;
}

function validarTempoToken($data_token)
{
    $dataInicial = new DateTime($data_token);
    $dataFinal = new DateTime();

    $intervalo = $dataInicial->diff($dataFinal);
    $minutosTotais = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i;
    return $minutosTotais < 300;
}

// if (isset($_GET['crp']) && !empty($_GET['crp'])) {
//     $crp = $_GET['crp'];
//     $crp = base64_decode($crp);
//     $json_string = decryptData($crp, $password_decrypted);
//     $array_parametros = json_decode($json_string, true);
//     // var_dump($array_parametros);
// }

function encryptData($data, $password)
{
    $parametros = array();
    parse_str($data, $parametros);
    $data = json_encode($parametros);

    $key = hash('sha256', $password, true);

    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

    $encryptedDataWithIV = $iv . $encryptedData;

    return base64_encode(base64_encode($encryptedDataWithIV));
}
