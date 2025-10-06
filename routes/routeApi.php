<?php
include_once __DIR__ . ('/../app/core/includes.php');
include_once __DIR__ . ('/../app/Controller/AuthController.php');
require_once __DIR__ . '/../helpers/helpers.php';
include_once __DIR__ . '/../session.php';
include_once __DIR__ . '/../routes/navigate.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = (array) trata_json_request($json);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// $uri = str_replace('migracao_sistema/', '', $uri);

$baseDir = __DIR__ . '/../app/Controller/';

$explodeUri = explode('/', $uri);


switch ($uri) {
    case 'layout_colunas/novaOrdenacao':
        require $baseDir . 'Layout_colunasController.php';
        $explodeUri[1]($data);
        break;
    // ==============================================
    case 'modelos/getModelos':
        require $baseDir . 'ModelosController.php';
        $explodeUri[1]($data);
        break;
    // ==============================================
    case 'modelos/store':
        require $baseDir . 'ModelosController.php';
        $explodeUri[1]($data);
        break;
    // ==============================================
    case 'conversao/salvaArquivo':
        require  $baseDir . 'ConversaoController.php';
        $explodeUri[1]($data);
        break;
    // ==============================================
    case 'conversao/salvaVinculacaoConvertidoLayout':
        require  $baseDir . 'ConversaoController.php';
        $explodeUri[1]($data);
        break;
    // ==============================================
    case 'conversao/EditVinculacaoArquivo':
        require  $baseDir . 'ConversaoController.php';
        $explodeUri[1]($data);
        break;
    // ==============================================
    case 'conversao/removeVinculacaoConvertidoLayout':
        require  $baseDir . 'ConversaoController.php';
        $explodeUri[1]($data);
        break;
    // ==============================================
    case 'conversao/atualizaColunaDePara':
        require  $baseDir . 'ConversaoController.php';
        $explodeUri[1]($data);
        break;

    // ==============================================
    case 'modelo/processaArquivo':
        require $baseDir . 'ModeloController.php';
        $explodeUri[1]($data);
        break;

    // ==============================================
    case 'arquivo/downloadArquivo':
        require  $baseDir . 'ArquivoController.php';
        $explodeUri[1]($data);
        break;
    default:
        return_api(404, 'not found');
}
