<?php

function getPassword()
{
    return "_9Nc32@df.0c";
}

function db_vars($banco)
{


    if ($banco == 'midas') {
        return  [
            'host' => $_SESSION['servidor_banco_dados'],
            'user' => $_SESSION['usuario_banco_dados'],
            'pass' => $_SESSION['senha_banco_dados'],
            'name' => $_SESSION['nome_banco_dados'],
        ];
    }
    if ($banco == 'caminho') {
        if ($_SESSION['ambiente'] == 'D') {
            require __DIR__ . '/../../conexao_teste/conexaodados.php';
        } else {
            require __DIR__ . '/../../conexao/conexaodados.php';
        }
        return  [
            'host' => $hostdb,
            'user' => $userdb,
            'pass' => $passdb,
            'name' => 'midas_caminho',
        ];
    }

    if ($banco == 'portais') {
        if ($_SESSION['ambiente'] == 'D') {
            require __DIR__ . '/../../conexao_teste/conexaodados portais.php';
        } else {
            require __DIR__ . '/../../conexao/conexaodados portais.php';
        }

        return  [
            'host' => $host_p,
            'user' => $username_p,
            'pass' => $password_p,
            'name' => $database_p,
        ];
    }
}
