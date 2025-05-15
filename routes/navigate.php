<?php

function redirect($modulo)
{
    if (!isAuthenticated()) {
        require './views/auth/login.php';
        return;
    }

    $caminho_modulo = $modulo;
    $explode = explode('/', $caminho_modulo);
    $_SESSION['rota_atual'] = $explode[0];
    // var_dump($_SESSION['rota_atual']);
    // die;

    $arquivo_controller_exist = __DIR__ . '/../app/Controller/' . ucfirst($explode[0]) . 'Controller.php';
    $retorno = [];

    if (file_exists($arquivo_controller_exist)) {
        include $arquivo_controller_exist;

        if (function_exists($explode[1])) {
            $retorno = $explode[1]();
        } else {
            var_dump('função não encontradata em: ' . ucfirst($explode[0]) . 'Controller.php');
        }
    }

    foreach ($retorno['data'] as $chave => $valor) {
        $$chave = $valor;
    }

    $pagina = __DIR__ . '/../views/' . $retorno['view'] . '.php';

    require $pagina;
}
