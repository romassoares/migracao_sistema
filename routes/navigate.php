<?php

function redirect($modulo)
{
    if (!isAuthenticated()) {
        require './views/auth/login.php';
        return;
    }

    $caminho_modulo = $modulo;
    $explode = explode('/', $caminho_modulo);

    $arquivo_controller_exist = __DIR__ . '/../app/Controller/' . ucfirst($explode[0]) . 'Controller.php';

    if (!file_exists($arquivo_controller_exist)) {
        var_dump('Controller não existe em: ' . $arquivo_controller_exist . 'Controller.php');
    }
    // notdie('antes include');
    include $arquivo_controller_exist;
    // notdie('depois include');

    if (!function_exists($explode[1])) {
        var_dump('função não existe em: ' . ucfirst($explode[0]) . 'Controller.php');
    }

    $retorno = $explode[1]();

    if (!empty($retorno['function'])) {
        header('Location:' . $retorno['function']);
        exit;
    }


    foreach ($retorno['data'] as $chave => $valor) {
        $$chave = $valor;
    }

    $pagina = __DIR__ . '/../views/' . $retorno['view'] . '.php';

    require $pagina;
}
