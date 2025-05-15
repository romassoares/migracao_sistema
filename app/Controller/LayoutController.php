<?php
include_once(__DIR__ . '/../core/includes.php');

function index()
{
    $sql = 'SELECT * FROM layout';
    $layouts = metodo_all($sql, 'migracao');
    return ['view' => 'layout/index', 'data' => ['layouts' => $layouts]];
}
