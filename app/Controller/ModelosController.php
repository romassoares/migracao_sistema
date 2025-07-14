<?php
include_once(__DIR__ . '/../core/includes.php');
include_once(__DIR__ . '/../../routes/navigate.php');

function index()
{
    $sql_modelos = 'SELECT * FROM modelos';
    $modelos = metodo_all($sql_modelos, 'migracao');

    return ['view' => 'conversao/index', 'data' => ['modelos' => $modelos], 'function' => ''];
}

function getModelos($data)
{
    $sql_modelos = 'SELECT id_modelo, nome_modelo, descr_tipo_arquivo 
        FROM modelos 
        LEFT JOIN tipos_arquivos t ON modelos.id_tipo_arquivo = t.id_tipo_arquivo
        WHERE id_concorrente = ' . $data['id_concorrente'] . ' AND ativo = 1';
    $modelos = metodo_all($sql_modelos, 'migracao');

    return_api(200, '', ['modelos' => $modelos]);
}


function store($data)
{
    $regras = [
        'nome_modelo' => ['required' => true, 'type' => 'string'],
        'id_layout' => ['required' => true, 'type' => 'int'],
        'id_concorrente' => ['required' => true, 'type' => 'int'],
        'id_tipo_arquivo' => ['required' => true, 'type' => 'int']
    ];

    $request = validateRequest($data, $regras);

    if (!$request['valido']) {
        return_api(400, $request['erros'], []);
        return;
    }

    $dados = $request['dados'];
    $dados['modelo_novo'] = 's';
    $dados['ativo'] = 1;

    $sql_insert_modelo = "INSERT INTO modelos (nome_modelo, id_layout, id_concorrente, id_tipo_arquivo, modelo_novo, ativo) VALUES (?,?,?,?,?,?)";
    $id_modelo = insert_update($sql_insert_modelo, "siiisi", $dados, 'migracao');

    $sql_get_modelo = "SELECT m.id_modelo, m.nome_modelo, t.descr_tipo_arquivo, l.id AS id_layout, l.nome AS nome_layout 
    FROM modelos AS m 
    LEFT JOIN layout AS l ON m.id_layout = l.id 
    LEFT JOIN tipos_arquivos AS t ON m.id_tipo_arquivo = t.id_tipo_arquivo
    WHERE m.id_modelo = $id_modelo";
    $modelo = metodo_get($sql_get_modelo, 'migracao');

    return_api(200, '', ['modelo' => $modelo]);
}

function update()
{
    $regras = [
        'id' => ['required' => true, 'type' => 'int'],
        'nome' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($_POST, $regras);

    $dados = $request['dados'];

    $sql = "UPDATE layout SET nome = ? WHERE id = ? ORDER BY nome ASC";

    insert_update($sql, "si", [$dados['nome'], $dados['id']], 'migracao');

    return ['view' => '', 'data' => [], 'function' => 'index'];
}
