<?php
include_once(__DIR__ . '/../core/includes.php');

function index()
{
    $sql = "SELECT * FROM clientes LIMIT 1";
    $cliente = metodo_get($sql, 'migracao');

    $sql = "SELECT * FROM usuarios LIMIT 1";
    $usuario = metodo_get($sql, 'migracao');
    $_SESSION['login_usuario'] = $usuario->login_usuario;
    $_SESSION['nome_cliente'] = $cliente->nome_cliente;
    return ['view' => 'dashboard', 'data' => []];
}
