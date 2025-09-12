<?php
include_once __DIR__ . ('/../app/core/includes.php');
include_once __DIR__ . ('/../app/Controller/AuthController.php');
require_once __DIR__ . '/../helpers/helpers.php';
include_once __DIR__ . '/../session.php';
include_once __DIR__ . '/../routes/navigate.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$uri = parse_url($uri, PHP_URL_PATH);

$uri = trim($uri, '/');
// dd($uri);

// $uri = str_replace('', '', $uri);
// $uri = str_replace('migracao_sistema/', '', $uri);

// if (!isAuthenticated())
//     return redirect('auth/login');

// if (!companySelected())
//     return redirect('auth/selectCompany');

// notdie($uri);
// if (strpos($uri, $_SESSION['rota_atual']) === 0) {
//     $uri = substr($uri, strlen($_SESSION['rota_atual']));
//     $uri = ltrim($uri, '/'); // remove a barra inicial se existir
// }


switch ($uri) {
    case '':
        redirect('dashboard/index');
        break;
    // ==============================================
    case 'auth/login':
        require __DIR__ . '/../views/auth/login.php';
        break;
    case 'auth/companys':
        require __DIR__ . '/../views/auth/selectCompany.php';
        break;
    case 'auth/new_company':
        require __DIR__ . '/../views/auth/newCompany.php';
        break;
    case 'auth/trocar_senha':
        redirect('auth/trocarSenha');
        break;
    case 'auth/salvar_senha':
        redirect('auth/salvarSenha');
        break;
    case 'auth/logout':
        redirect('auth/logout');
        // ==============================================
    case 'layout/index':
        redirect('layout/index');
        break;
    case 'layout/store':
        redirect('layout/store');
        break;
    case 'layout/update':
        redirect('layout/update');
        break;
    // ==============================================
    case 'concorrente/index':
        redirect('concorrente/index');
        break;
    case 'concorrente/store':
        redirect('concorrente/store');
        break;
    case 'concorrente/update':
        redirect('concorrente/update');
        break;
    // ==============================================
    case 'layout_colunas/index':
        redirect('layout_colunas/index');
        break;
    case 'layout_colunas/edit':
        redirect('layout_colunas/edit');
        break;
    case 'layout_colunas/store':
        redirect('layout_colunas/store');
        break;
    case 'layout_colunas/update':
        redirect('layout_colunas/update');
        break;
    case 'layout_colunas/delete':
        redirect('layout_colunas/delete');
        break;
    case 'layout_colunas/deleteConteudosColuna':
        redirect('layout_colunas/deleteConteudosColuna');
        break;
    // ==============================================
    case 'conversao/index':
        redirect('conversao/index');
        break;
    case 'conversao/uploadArquivo':
        require __DIR__ . '/../app/Controller/ConversaoController.php';
        uploadArquivo();
        break;

    // ==============================================
    case 'modelo/index':
        redirect('modelo/index');
        break;
    case 'modelo/create':
        redirect('modelo/create');
        break;
    case 'modelo/store':
        redirect('modelo/store');
        break;
    case 'modelo/detalhar':
        redirect('modelo/detalhar');
        break;
    case 'conversao/convertidos':
        redirect('conversao/convertidos');
        break;

    default:
        if (!isAuthenticated()) {
            header('Location: /auth/login');
            exit();
        } else if (!companySelected()) {
            header('Location: /auth/companys');
            exit();
        }
        die('view not found');
        break;
}
