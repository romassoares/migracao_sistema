<?php
include_once __DIR__ . ('/../app/core/includes.php');
include_once __DIR__ . ('/../app/Auth/auth.php');
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

$uri = str_replace('migracao_sistema', '', $uri);

// if (!isAuthenticated())
//     return redirect('auth/login');

// if (!companySelected())
//     return redirect('auth/selectCompany');


// $_SESSION['logged'] = true;
$baseDir = __DIR__ . '/../app/Controller/';

$explodeUri = explode('/', $uri);

switch ($uri) {
    case '/layout_colunas/novaOrdenacao':
        require $baseDir . 'Layout_colunasController.php';
        $explodeUri[2]($data);
        break;
    // ==============================================

    // ==============================================
    default:
        return_api(404, 'not found');
}
