<?php
include_once(__DIR__ . '/../core/includes.php');
include_once(__DIR__ . '/../../routes/navigate.php');

function index()
{
    $id = $_GET['id'];

    $sql = "SELECT * FROM layout where id = $id";
    $layout = metodo_get($sql, 'migracao');

    $sql = "SELECT * FROM layout_colunas where id_layout = $id";
    $layout_colunas = metodo_all($sql, 'migracao');

    return ['view' => 'layout/colunas/index', 'data' => ['layout_colunas' => $layout_colunas, 'layout' => $layout], 'function' => ''];
}

function edit()
{
    $regras = [
        'id_layout' => ['required' => true, 'type' => 'string'],
        'id_layout_coluna' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($_GET, $regras);

    $id_layout = $request['dados']['id_layout'];
    $id_layout_coluna = $request['dados']['id_layout_coluna'];

    $sql = "SELECT 
    layout_colunas.*, 
    GROUP_CONCAT(
     	CONCAT(
             'id_modelo_coluna@@', IFNULL(l_c_d.id_modelo_coluna,''),
            '##conteudo_de@@', IFNULL(l_c_d.conteudo_de,''), 
            '##id_layout_coluna_conteudos@@', IFNULL(l_c_d.id_layout_coluna_conteudos,''), 
            '##Conteudo_para_livre@@', IFNULL(l_c_d.Conteudo_para_livre,''), 
            '##substituir@@', IFNULL(l_c_d.substituir,''), 
            '##ordem@@', IFNULL(l_c_d.ordem,'')
            )
            SEPARATOR ' || '
        ) AS flags 
    FROM layout_colunas 
    LEFT JOIN layout_coluna_depara AS l_c_d ON layout_colunas.id = l_c_d.id_layout_coluna  
    WHERE layout_colunas.id_layout = $id_layout AND layout_colunas.id = $id_layout_coluna";
    $layout_coluna = metodo_get($sql, 'migracao');

    $layout_coluna->flags = trata_group_concat((array) $layout_coluna->flags, 'flags');

    // dd($layout_coluna);

    return ['view' => 'layout/colunas/form', 'data' => ['layout_coluna' => $layout_coluna], 'function' => ''];
}

function store()
{
    $regras = [
        'nome' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($_POST, $regras);

    $dados = $request['dados'];
    $dados['ativo'] = 1;

    $sql = "INSERT INTO layout_colunas (nome, ativo) VALUES (?,?)";
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

    $sql = "UPDATE layout_colunas SET nome = ? WHERE id = ?";

    insert_update($sql, "si", [$dados['nome'], $dados['id']], 'migracao');

    return ['view' => '', 'data' => [], 'function' => 'index'];
}
