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
    case 'layout/delete':
        redirect('layout/delete');
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
        require __DIR__ . '/../app/Controller/ArquivoController.php';
        uploadArquivo();
        break;
    case 'conversao/depara':
        redirect('conversao/deparaIndex');
        break;
    case 'conversao/deparaSalvar':
        redirect('conversao/deparaSalvar');
        break;
    case 'conversao/deletarTodosDepara':
        redirect('conversao/deletarTodosDepara');
        break;
    case 'conversao/deletarDepara':
        redirect('conversao/deletarDepara');
        break;
    case 'conversao/detecta-movimento':
        return_api(200, '', []);
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
    // case 'modelo/getModeloColunas':
    //     redirect('modelo/getModeloColunas');
    //     break;

    case 'conversao/convertidos':
        redirect('conversao/convertidos');
        break;

    case 'arquivo/lerArquivo':
        require __DIR__ . '/../app/Controller/ArquivoController.php';
        lerArquivo();
        break;

    case 'vista/index':
        redirect('vista/index');
        break;

    case 'vista/store':
        redirect('vista/store');
        break;

    case 'vista/editarModelo':
        redirect('vista/editarModelo');
        break;

    case 'status/processo':
        echo json_encode(file_get_contents(__DIR__ . '/../app/Jobs/status/status.json'));
        break;

    default:
        if (!isAuthenticated()) {
            header('Location: /auth/login');
            exit();
        } else if (!companySelected()) {
            header('Location: /auth/companys');
            exit();
        }
        var_dump($uri);
        die('view not found');
        break;
}
