<?php
include_once __DIR__ . ('/../app/core/includes.php');
include_once __DIR__ . ('/../app/Auth/auth.php');
require_once __DIR__ . '/../helpers/helpers.php';
include_once __DIR__ . '/../session.php';
include_once __DIR__ . '/../routes/navigate.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$uri = parse_url($uri, PHP_URL_PATH);

$uri = trim($uri, '/');

$uri = str_replace('migracao_sistema', '', $uri);

// if (!isAuthenticated())
//     return redirect('auth/login');

// if (!companySelected())
//     return redirect('auth/selectCompany');

// notdie($uri);
// if (strpos($uri, $_SESSION['rota_atual']) === 0) {
//     $uri = substr($uri, strlen($_SESSION['rota_atual']));
//     $uri = ltrim($uri, '/'); // remove a barra inicial se existir
// }
// dd($uri);

$_SESSION['logged'] = true;

switch ($uri) {
    case '':
        redirect('dashboard/index');
        break;
    // ==============================================
    case '/layout/index':
        redirect('layout/index');
        break;
    case '/layout/store':
        redirect('layout/store');
        break;
    case '/layout/update':
        redirect('layout/update');
        break;
    // ==============================================
    case '/concorrente/index':
        redirect('concorrente/index');
        break;
    case '/concorrente/store':
        redirect('concorrente/store');
        break;
    case '/concorrente/update':
        redirect('concorrente/update');
        break;
    // ==============================================
    default:
        die('view not found');
        break;
}
