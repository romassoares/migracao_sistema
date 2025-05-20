<?php
include_once(__DIR__ . '/../core/includes.php');
include_once(__DIR__ . '/../../routes/navigate.php');

function index()
{
    $id = $_GET['id'];

    $sql = "SELECT * FROM layout where id = $id";
    $layout = metodo_get($sql, 'migracao');

    $sql = "SELECT * FROM layout_colunas where id_layout = $id ORDER BY posicao asc";
    $layout_colunas = metodo_all($sql, 'migracao');

    $ids_order = [];

    foreach ($layout_colunas as $item) {
        if (isset($item['posicao'])) {
            $ids_order[] = $item['id'];
        }
    }

    return ['view' => 'layout/colunas/index', 'data' => ['layout_colunas' => $layout_colunas, 'layout' => $layout, 'ids_order' => $ids_order], 'function' => ''];
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
             'colunas_conteudo_id@@', IFNULL(l_c_d.id,'') ,
            '##id_layout_colunas@@', IFNULL(l_c_d.id_layout_colunas,''), 
            '##conteudo@@', IFNULL(l_c_d.conteudo,''), 
            '##descricao@@', IFNULL(l_c_d.descricao,'')
            )
            SEPARATOR ' || '
        ) AS flags 
    FROM layout_colunas 
    inner JOIN layout_coluna_conteudos AS l_c_d ON layout_colunas.id = l_c_d.id_layout_colunas  
    -- inner JOIN layout_coluna_depara AS l_c_d ON layout_colunas.id = l_c_d.id_layout_coluna  
    WHERE layout_colunas.id_layout = $id_layout AND layout_colunas.id = $id_layout_coluna";
    $layout_coluna = metodo_get($sql, 'migracao');

    if (!is_null($layout_coluna->flags))
        $layout_coluna->flags = trata_group_concat((array) $layout_coluna->flags, 'flags');

    return ['view' => 'layout/colunas/form', 'data' => ['layout_coluna' => $layout_coluna], 'function' => ''];
}

function store()
{
    $regras = [
        'id_layout' => ['required' => true, 'type' => 'int'],
        'nome_exibicao' => ['required' => true, 'type' => 'string'],
        'tipo' => ['required' => true, 'type' => 'string'],
        'obrigatorio' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($_POST, $regras);
    $dados = $request['dados'];
    // dd($dados);

    $sql = "INSERT INTO layout_colunas (id_layout, nome_exibicao, tipo, obrigatorio, posicao)
    VALUES (?, ?, ?, ?, COALESCE(
                            (SELECT MAX_POS + 1 FROM (SELECT MAX(posicao) AS MAX_POS FROM layout_colunas WHERE id_layout = ?
                            ) AS temp),
                        1)
        )";
    insert_update($sql, "issii", [
        $dados['id_layout'],
        $dados['nome_exibicao'],
        $dados['tipo'],
        $dados['obrigatorio'],
        $dados['id_layout']
    ], 'migracao');

    return ['view' => '', 'data' => [], 'function' => 'index/?id=' . $dados['id_layout']];
}



function update()
{
    global $db;

    $regras = [
        'id_layout' => ['required' => true, 'type' => 'int'],
        'id_layout_coluna' => ['required' => true, 'type' => 'int'],
        'nome_exibicao' => ['required' => true, 'type' => 'string'],
        'tipo' => ['required' => true, 'type' => 'string'],
        'ativo' => ['required' => true, 'type' => 'string'],
        'obrigatorio' => ['required' => true, 'type' => 'string'],
        'conteudo' => ['required' => false, 'type' => 'array'],
        'conteudoNew' => ['required' => false, 'type' => 'array']
    ];
    $request = validateRequest($_POST, $regras);

    $dados = $request['dados'];
    $id_layout = $dados['id_layout'];
    $id_layout_coluna = $dados['id_layout_coluna'];

    // =======================================================================================
    // =======================================================================================

    if (isset($dados['conteudo']) && count($dados['conteudo']) > 0) {
        $qnt_items_conteudo = count($dados['conteudo']['colunas_conteudo_id']);
        $colunas_conteudo_id = $dados['conteudo']['colunas_conteudo_id'];
        $nomes_data = $dados['conteudo']['nome'];
        $decricao_data = $dados['conteudo']['descricao'];

        for ($i = 0; $i < $qnt_items_conteudo; $i++) {
            $conteudo = $nomes_data[$i];
            $descricao = $decricao_data[$i];
            $id = $colunas_conteudo_id[$i];

            $sqlBaseUpdate = "UPDATE layout_coluna_conteudos SET conteudo = '$conteudo', descricao = '$descricao' WHERE id_layout_colunas = $id_layout_coluna AND id = $id";
            $db->connect('migracao')->query($sqlBaseUpdate);
        }
    }

    // =======================================================================================
    // =======================================================================================


    if (isset($dados['conteudoNew']) && count($dados['conteudoNew']) > 0) {
        $qnt_items_new = count($dados['conteudoNew']['nome']);
        $nomes_data_new = $dados['conteudoNew']['nome'];
        $decricao_data_new = $dados['conteudoNew']['descricao'];

        $sqlBaseInsert = "INSERT INTO layout_coluna_conteudos (id_layout_colunas, conteudo, descricao) VALUES";
        for ($i = 0; $i < $qnt_items_new; $i++) {
            $sqlBaseInsert .= " ($id_layout_coluna, '$nomes_data_new[$i]', '$decricao_data_new[$i]'),";
        }
        $sqlBaseInsert = substr($sqlBaseInsert, 0, -1);
        // dd($sqlBaseInsert);
        $db->connect('migracao')->query($sqlBaseInsert);
    }

    $sql = "UPDATE layout_colunas SET nome_exibicao = ?, tipo = ?, ativo = ?, obrigatorio = ? WHERE id = ? AND id_layout = ?";

    insert_update($sql, 'ssiiii', [
        $dados['nome_exibicao'],
        $dados['tipo'],
        $dados['ativo'],
        $dados['obrigatorio'],
        $dados['id_layout_coluna'],
        $dados['id_layout'],
    ], 'migracao');

    return ['view' => '', 'data' => [], 'function' => "edit/?id_layout=$id_layout&id_layout_coluna=$id_layout_coluna"];
}

function deleteConteudosColuna()
{
    $regras = [
        'id_layout_coluna' => ['required' => true, 'type' => 'string'],
        'id_layout' => ['required' => true, 'type' => 'string'],
    ];
    $request = validateRequest($_GET, $regras);

    $dados = (object) $request['dados'];

    $sql = "DELETE FROM layout_coluna_conteudos WHERE id_layout_colunas = ?";
    insert_update($sql, 'i', [$dados->id_layout_coluna], 'migracao');

    // dd("edit/?id_layout=" . $dados->id_layout . "&id_layout_coluna=" . $dados->id_layout_coluna);

    return ['view' => '', 'data' => [], 'function' => "edit/?id_layout=" . $dados->id_layout . "&id_layout_coluna=" . $dados->id_layout_coluna];
}


function delete()
{
    $regras = [
        'id_layout' => ['required' => true, 'type' => 'string'],
        'id_layout_coluna' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($_GET, $regras);

    $id_layout = $request['dados']['id_layout'];
    $id_layout_coluna = $request['dados']['id_layout_coluna'];

    $sql = "DELETE FROM layout_colunas WHERE id_layout = ? AND id = ?";

    insert_update($sql, 'ii', [$id_layout, $id_layout_coluna], 'migracao');

    return ['view' => '', 'data' => [], 'function' => "index/?id=" . $id_layout];
}

function novaOrdenacao($data)
{
    $database = 'migracao';
    $db = new DB();

    $regras = [
        'posicao_alvo' => ['required' => true, 'type' => 'int'],
        'posicao_dragged' => ['required' => true, 'type' => 'string'],
        'id_layout' => ['required' => true, 'type' => 'int']
    ];
    $request = validateRequest($data, $regras);
    dd($request);
    $id_layout = $request['dados']['id_layout'];
    $posicao_alvo = $request['dados']['posicao_alvo'];
    $posicao_dragged = intval($request['dados']['posicao_dragged']);

    $sql = "SELECT * FROM layout_colunas WHERE id = $id_layout";
    $item_alvo = metodo_get($sql, 'migracao');
    // dd($item_alvo);

    $db->beginTransaction($database);

    try {
        if ($posicao_alvo > $posicao_dragged) { // Mover de cima pra baixo
            $sql = "UPDATE layout_colunas 
                    SET posicao = posicao - 1 
                    WHERE id_layout = ? 
                    AND posicao > ? 
                    AND posicao <= ?";
            insert_update($sql, "iii", [$id_layout, $posicao_dragged, $posicao_alvo], $database);
        } else { // Mover de baixo pra cima
            $sql = "UPDATE layout_colunas 
                    SET posicao = posicao + 1 
                    WHERE id_layout = ? 
                    AND posicao >= ? 
                    AND posicao < ?";
            insert_update($sql, "iii", [$id_layout, $posicao_alvo, $posicao_dragged], $database);
        }

        // Atualiza item arrastado
        $sql_dragged = "UPDATE layout_colunas 
                        SET posicao = ? 
                        WHERE id_layout = ? 
                        AND id = ?";
        insert_update($sql_dragged, "iii", [$posicao_alvo, $item_alvo->id_layout, $item_alvo->id], $database);

        $db->commit($database);
        return_api(200);
    } catch (Exception $e) {
        $db->rollBack($database);
        return_api(500);
        die("Erro ao reordenar colunas: " . $e->getMessage());
    }
}
