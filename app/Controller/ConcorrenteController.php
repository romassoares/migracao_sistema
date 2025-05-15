<?php
include_once(__DIR__ . '/../core/includes.php');

function index()
{
    $sql = 'SELECT * FROM concorrentes';
    $concorrentes = metodo_all($sql, 'migracao');
    return ['view' => 'concorrente/index', 'data' => ['concorrentes' => $concorrentes], 'function' => ''];
}

function store()
{
    $regras = [
        'nome' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($_POST, $regras);

    $dados = $request['dados'];
    // $dados['ativo'] = 1;

    $sql = "INSERT INTO concorrentes (nome) VALUES (?)";

    insert_update($sql, "s", [$dados['nome']], 'migracao');

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

    $sql = "UPDATE concorrentes SET nome = ? WHERE id = ?";

    insert_update($sql, "si", [$dados['nome'], $dados['id']], 'migracao');

    return ['view' => '', 'data' => [], 'function' => 'index'];
}
