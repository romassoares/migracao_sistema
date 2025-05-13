<?php

require_once  __DIR__ . '/../app/Portal.php';
require_once  __DIR__ . '/../app/Empresa.php';
require_once  __DIR__ . '/../app/Imovel.php';
require_once __DIR__ . '/../helpers/helpers.php';
include_once __DIR__ . '/../session.php';
include_once __DIR__ . '/../vars.php';

class Route
{

    public function __construct() {}

    public function index()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (isset($_GET['crp']) && !empty($_GET['crp'])) {

            $crp = $_GET['crp'];
            $crp = base64_decode($crp);
            // adicionando função com password
            $json_string = decryptData($crp, getPassword());
            $array_parametros = json_decode($json_string, true);
            if (empty($array_parametros['trb_codigo_infoideias'])) {
                $_SESSION['msg'] = "Código infoideias não encontrado. Faça login novamente";
            } else {
                $_SESSION['trb_codigo_infoideias'] = $array_parametros['trb_codigo_infoideias'];
                $_SESSION['trb_id_operador_envio'] = $array_parametros['trb_id_operador_envio'];
                $_SESSION['trb_nome_operador_envio'] = $array_parametros['trb_nome_operador_envio'];

                $_SESSION['ambiente'] = $array_parametros['ambiente'];
            }
        }

        echo "Página não encontrada";
    }

    public function redirect($arquivo, $data, $msg)
    {
        require $arquivo . '.php';
    }
}
