<?php
include_once __DIR__ . ('/../app/core/includes.php');
include_once __DIR__ . ('/../database/db.php');
include_once __DIR__ . ('/../app/Auth/auth.php');
require_once __DIR__ . '/../helpers/helpers.php';
include_once __DIR__ . '/../session.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// if (!isAuthenticated())
//     return redirect('auth/login');

// if (!companySelected())
//     return redirect('auth/selectCompany');


$_SESSION['logged'] = true;
$sql = "SELECT * FROM clientes LIMIT 1";
$cliente = metodo_get($sql, 'migracao');

$sql = "SELECT * FROM usuarios LIMIT 1";
$usuario = metodo_get($sql, 'migracao');

return redirect('dashboard', ['cliente' => $cliente, 'usuario' => $usuario]);

function redirect($arquivo, $data = [], $msg = '')
{

    // monta_objetos($data);
    if (!isAuthenticated()) {
        require './views/auth/login.php';
        return;
    }
    require './views/' . $arquivo . '.php';
}
