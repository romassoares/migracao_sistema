<?php
include_once __DIR__ . ('/../database/db.php');
include_once __DIR__ . ('/../app/Auth/auth.php');
include_once __DIR__ . ('/../app/core/methods.php');
require_once __DIR__ . '/../helpers/helpers.php';
include_once __DIR__ . '/../session.php';
include_once __DIR__ . '/../vars.php';


$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// if (!isAuthenticated())
//     return redirect('auth/login');

// if (!companySelected())
//     return redirect('auth/selectCompany');

return redirect('dashboard');

function redirect($arquivo, $data = [], $msg = '')
{
    if (!isAuthenticated()) {
        require './views/auth/login.php';
        return;
    }
    require './views/' . $arquivo . '.php';
}
