<?php
include_once(__DIR__ . '/../core/includes.php');
include_once(__DIR__ . '/../../routes/navigate.php');

function index()
{
    $sql = 'SELECT * FROM layout';

    $layouts = metodo_all($sql, 'migracao');

    return ['view' => 'layout/index', 'data' => ['layouts' => $layouts], 'function' => ''];
}

function store()
{
    $regras = [
        'nome' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($_POST, $regras);

    $dados = $request['dados'];
    $dados['ativo'] = 1;

    $sql = "INSERT INTO layout (nome, ativo) VALUES (?,?)";

    insert_update($sql, "ss", $dados, 'migracao');

    return ['view' => '', 'data' => [], 'function' => 'index'];
}

function update()
{
    $regras = [
        'id' => ['required' => true, 'type' => 'int'],
        'nome' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($_POST, $regras);

    $dados = $request['dados'];
    // dd($dados);
    $sql = "UPDATE layout SET nome = ? WHERE id = ? ORDER BY nome ASC";

    insert_update($sql, "si", [$dados['nome'], $dados['id']], 'migracao');

    return ['view' => '', 'data' => [], 'function' => 'index'];
}
