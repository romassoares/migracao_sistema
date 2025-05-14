<?php

function getPassword()
{
    // return "";
}

function db_vars($banco)
{

    if ($banco == 'caminho') {
        // if ($_SESSION['ambiente'] == 'D') {
        //     require __DIR__ . '/../../conexao_teste/conexaodados.php';
        // } else {
        // require __DIR__ . '/../../../../_conexoes/conexao_caminho.php';
        // }
        // return  [
        // 'host' => $host_caminho,
        // 'user' => $username_caminho,
        // 'pass' => $password_caminho,
        // 'name' => $database_caminho,
        // ];
    }

    if ($banco == 'migracao') {
        return  [
            'host' => '',
            'user' => '',
            'pass' => '',
            'name' => '',
        ];
    }
}
