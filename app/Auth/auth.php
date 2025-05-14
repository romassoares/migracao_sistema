<?php

$db = new DB();

function isAuthenticated()
{
    return !isset($_SESSION['logged']) ? false : $_SESSION['logged'];
}

function login()
{
    $_SESSION['logged'] = true;
    // redirect('auth/selectCompany');
    return;
    global $db;

    dd('login function');
    $data['user'] = filter_input(INPUT_POST, 'user', FILTER_SANITIZE_SPECIAL_CHARS);

    if (filter_var($data['user'], FILTER_SANITIZE_SPECIAL_CHARS)) {

        $data['password'] = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

        $sql = 'SELECT * FROM table_name WHERE user=? AND password=? LIMIT 1';

        $mysqli = $db->connect('midas_caminho');
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param('si', [$data['user'], $data['password']]);

            if ($stmt->execute()) {

                $user = $stmt->fetch();
                if ($user) {
                    $_SESSION['user'] = $user;
                    // $_SESSION['user'] = [];
                    $_SESSION['logged'] = true;

                    $route->redirect('auth/selectCompany');
                } else {
                    $route->redirect('auth/login');
                }
            } else {
                $route->redirect('auth/login');
            }
        }
    } else {
        $route->redirect('auth/login');
    }
}

function companySelected()
{
    return !isset($_SESSION['company_selected']) ? false : $_SESSION['company_selected'];
}

function ckeck_company()
{
    global $db, $route;

    $data['company'] = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_SPECIAL_CHARS);

    if (false) {
        $_SESSION['company_selected'] = true;
    } else {
        $_SESSION['company_selected'] = false;
    }
}
