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

function novaOrdenacao($data)
{
    $database = 'migracao';
    $db = new DB();

    $regras = [
        'id_layout' => ['required' => true, 'type' => 'int'],
        'id_layout_coluna_alvo' => ['required' => true, 'type' => 'int'],
        'posicao_alvo' => ['required' => true, 'type' => 'int'],
        'posicao_dragged' => ['required' => true, 'type' => 'int'],
        'id_layout_coluna_dragged' => ['required' => true, 'type' => 'int']
    ];
    $request = validateRequest($data, $regras);

    $id_layout = $request['dados']['id_layout'];
    // $id_layout_coluna_dragged = $request['dados']['id_layout_coluna_dragged'];
    // $posicao_alvo = $request['dados']['posicao_alvo'];
    // $posicao_dragged = $request['dados']['posicao_dragged'];

    // Se nada mudou, retorna
    // if ($posicao_alvo == $posicao_dragged) {
    //     return_api(202);
    //     return;
    // }

    $db->beginTransaction($database);

    try {
        // if ($posicao_alvo > $posicao_dragged) {
        //     // Mover de cima pra baixo
        //     $sql = "UPDATE layout_colunas 
        //             SET posicao = posicao - 1 
        //             WHERE id_layout = ? 
        //             AND posicao > ? 
        //             AND posicao <= ?";
        //     insert_update($sql, "iii", [$id_layout, $posicao_dragged, $posicao_alvo], $database);
        // } else {
        //     // Mover de baixo pra cima
        //     $sql = "UPDATE layout_colunas 
        //             SET posicao = posicao + 1 
        //             WHERE id_layout = ? 
        //             AND posicao >= ? 
        //             AND posicao < ?";
        //     insert_update($sql, "iii", [$id_layout, $posicao_alvo, $posicao_dragged], $database);
        // }

        // // Atualiza item arrastado
        // $sql_dragged = "UPDATE layout_colunas 
        //                 SET posicao = ? 
        //                 WHERE id_layout = ? 
        //                 AND id = ?";
        // insert_update($sql_dragged, "iii", [$posicao_alvo, $id_layout, $id_layout_coluna_dragged], $database);

        // Renumera todas as posições sequencialmente a partir de 1
        $query = metodo_all("SELECT id FROM layout_colunas WHERE id_layout = {$id_layout} ORDER BY posicao ASC", $database);

        $novaPosicao = 1;
        $cases = '';
        $ids = [];

        foreach ($query as $coluna) {
            $id = (int)$coluna['id'];
            $cases .= "WHEN {$id} THEN {$novaPosicao} ";
            $ids[] = $id;
            $novaPosicao++;
        }

        if (!empty($ids)) {
            $idsList = implode(',', $ids);
            $sql = "UPDATE layout_colunas 
            SET posicao = CASE id {$cases} END 
            WHERE id IN ({$idsList})";

            metodo_all($sql, $database);
        }

        $db->commit($database);
        return_api(200);
    } catch (Exception $e) {
        $db->rollBack($database);
        return_api(500);
        die("Erro ao reordenar colunas: " . $e->getMessage());
    }
}



// item1 = posicao_alvo1
// item2 = posicao_alvo2
// item3 = posicao_alvo3
// item4 = posicao_alvo4
// item5 = posicao_alvo5
// item6 = posicao_alvo6

// //-----
// item2 = posicao_alvo1
// item3 = posicao_alvo2
// item4 = posicao_alvo3
// item1 = posicao_alvo4
// item5 = posicao_alvo5
// item6 = posicao_alvo6
// //-----
// item6 = posicao_alvo1
// item2 = posicao_alvo2
// item3 = posicao_alvo3
// item4 = posicao_alvo4
// item1 = posicao_alvo5
// item5 = posicao_alvo6