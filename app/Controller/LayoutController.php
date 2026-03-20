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

function delete()
{
    $regras = [
        'id' => ['required' => true, 'type' => 'int']
    ];

    $request = validateRequest($_GET, $regras);
    $dados = (object) $request['dados'];

    $database = "migracao";
    $db = new DB();
    $db->beginTransaction($database);

    try {

        insert_update("DELETE lcc 
        FROM layout_coluna_conteudos lcc
        JOIN layout_colunas lc ON lcc.id_layout_colunas = lc.id
        WHERE lc.id_layout = ?", "i", [$dados->id], $database);

        insert_update("DELETE lcd
        FROM layout_coluna_depara lcd
        JOIN layout_colunas lc ON lcd.id_layout_coluna = lc.id
        WHERE lc.id_layout = ?", "i", [$dados->id], $database);

        insert_update("DELETE mc
        FROM modelos_colunas mc
        JOIN layout_colunas lc ON mc.id_layout_coluna = lc.id
        WHERE lc.id_layout = ?", "i", [$dados->id], $database);

        insert_update("DELETE a
        FROM arquivos a
        JOIN modelos m ON a.id_modelo = m.id_modelo
        WHERE m.id_layout = ?", "i", [$dados->id], $database);

        insert_update("DELETE FROM modelos
        WHERE id_layout = ?", "i", [$dados->id], $database);

        insert_update("DELETE FROM layout_colunas
        WHERE id_layout = ?", "i", [$dados->id], $database);

        insert_update("DELETE FROM layout
        WHERE id = ?
    ", "i", [$dados->id], $database);

        $db->commit($database);

        return [
            'view' => '',
            'data' => [],
            'function' => "index/?id=" . $dados->id
        ];
    } catch (\Throwable $e) {
        $db->rollBack($database);
        die("Erro deletar layout: " . $e->getMessage());
    }
}
