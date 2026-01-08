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
    case 'modelo/duplicar':
        require  $baseDir . 'ModeloController.php';
        $explodeUri[1]($data);
        break;
    // ==============================================
    case 'modelos/duplicaModelo':
        require  $baseDir . 'ModelosController.php';
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
        $debug = false;

        $id = uniqid("job_", true);

        if ($debug) {
            require __DIR__ . '/../app/Jobs/worker.php';
        } else {

            file_put_contents(__DIR__ . '/../app/Jobs/status/status.json', json_encode([
                "status"   =>  "processing",
                "progress" => 1,
                "message"  => 'Iniciando...'
            ], JSON_PRETTY_PRINT));

            $worker = __DIR__ . '/../app/Jobs/worker.php';

            $json = base64_encode(json_encode($data));
            $sessionEncoded = base64_encode(json_encode($_SESSION));
            // var_dump($sessionEncoded);
            // die;
            $cmd = "C:\php83\php.exe \"$worker\" "
                . escapeshellarg($id) . " "
                . escapeshellarg(realpath(__DIR__ . "/../app/")) . " "
                . escapeshellarg($sessionEncoded) . " "
                . escapeshellarg($json);

            pclose(popen("start /B " . $cmd, "r"));
        }

        return_api(200, '', [
            "jobId" => $id
        ]);
        break;
    // ==============================================

    // ==============================================
    case 'vista/processarArquivo':
        require $baseDir . 'VistaController.php';
        $explodeUri[1]($data);
        break;

    // ==============================================
    case 'arquivo/downloadArquivo':
        require  $baseDir . 'ArquivoController.php';
        $explodeUri[1]($data);
        break;

    default:
        return_api(404, 'not found Api');
}
