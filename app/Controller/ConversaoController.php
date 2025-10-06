<?php
include_once(__DIR__ . '/../core/includes.php');
include_once(__DIR__ . '/../../routes/navigate.php');

ini_set('memory_limit', '512M');

function index()
{
    // $id_modelo = isset($_GET['id_modelo']) ? $_GET['id_modelo'] : '199';

    $id_modelo = isset($_GET['id_modelo']) ? $_GET['id_modelo'] : '';
    $id_arquivo = isset($_GET['id_arquivo']) ? $_GET['id_arquivo'] : '';
    // $nome_arquivo = isset($_GET['nome_arquivo']) ? $_GET['nome_arquivo'] : '';
    // $id_modelo = isset($_GET['id_modelo']) ? $_GET['id_modelo'] : '';

    $modelos_colunas = new stdClass();
    $modelo = new stdClass();

    if (!empty($id_modelo)) {
        $sql_modelo = "SELECT * FROM modelos WHERE id_modelo = $id_modelo";
        $modelo = metodo_get($sql_modelo, 'migracao');
        if (isset($modelo->id_modelo)) {
            $sql_modelos_colunas = "SELECT * FROM modelos_colunas WHERE id_modelo = $modelo->id_modelo AND id_concorrente = $modelo->id_concorrente";
            $modelos_colunas = metodo_all($sql_modelos_colunas, 'migracao');
        }
    }

    $sql_concorrentes = 'SELECT * FROM concorrentes';
    $concorrentes = metodo_all($sql_concorrentes, 'migracao');

    $sql_layouts = 'SELECT * FROM layout';
    $layouts = metodo_all($sql_layouts, 'migracao');

    $sql_tipo_arquivos = 'SELECT * FROM tipos_arquivos';
    $tipos_arquivo = metodo_all($sql_tipo_arquivos, 'migracao');

    return ['view' => 'conversao/index', 'data' => [
        'layouts' => $layouts,
        'concorrentes' => $concorrentes,
        'modelos_colunas' => $modelos_colunas,
        'modelo' => $modelo,
        'tipos_arquivo' => $tipos_arquivo,
        'id_arquivo' => $id_arquivo
    ], 'function' => ''];
}

function uploadArquivo()
{

    $id_modelo = $_POST['modelo_id'];
    $file = $_FILES;

    if (isset($file['arquivo'])) {

        $sql = "SELECT *, l.nome FROM modelos AS m
        LEFT JOIN tipos_arquivos as t ON m.id_tipo_arquivo = t.id_tipo_arquivo
        LEFT JOIN layout as l ON m.id_layout = l.id
        LEFT JOIN concorrentes as c ON m.id_concorrente = c.id
        WHERE id_modelo = $id_modelo";
        $modelo = metodo_get($sql, 'migracao');

        // ===============================================================

        $sql_layout_colunas = "SELECT * FROM layout_colunas WHERE id_layout = " . $modelo->id_layout . " ORDER BY posicao ASC";
        $layout_colunas = metodo_all($sql_layout_colunas, 'migracao');

        // ===============================================================

        // $connection = ftp_connect('ftp.localhost');
        // $login = ftp_login($connection, $ftp_user_name, $ftp_user_pass);

        // if (!$connection || !$login)
        //     die('Connection attempt failed!');

        $basename = basename($file['arquivo']['name']);

        // $remoteFile = '';
        // if ($_SESSION['ambiente'] == 'D') {
        //     $remoteFile =  'migracao/' . $basename;
        // } else {
        //     $remoteFile =  './' . $basename;
        // }

        $tmpFile = $_FILES['arquivo']['tmp_name'];

        $extension_file = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);

        $nomeArquivo = str_replace('.' . $extension_file, '', $basename);

        // if (ftp_put($connection, $remoteFile, $tmpFile, FTP_BINARY)) {
        //     ftp_close($connection);

        // converte arquivo para array
        $convert = new ConvertService();
        $converted = $convert->converter($tmpFile, $extension_file, $modelo->descr_tipo_arquivo);

        // =================================================
        // =================================================

        $qntItems = count($converted[1]);

        //definição do diretório base
        $dir_base = __DIR__ . '/../../assets/' . $_SESSION['company']['nome'] . '/' . $modelo->nome_modelo . '/' . $modelo->id_modelo . '/';

        //verifica se o diretório existe, se não existir cria
        if (!file_exists($dir_base))
            mkdir($dir_base, 0777, true);

        //verifica se o arquivo já existe, se existir adiciona a data e hora
        if (!copy($tmpFile, $dir_base . $nomeArquivo . '_' . date('i') . '_' . date('s') . '.' . $extension_file)) {
            return_api(404, 'Error ao salvar arquivo', []);
            return;
        }

        $nomeArquivoCopiado = $nomeArquivo . '_' . date('i') . '_' . date('s') . '.' . $extension_file;

        // =================================================
        // =================================================

        // Buscar se já existe o arquivo
        $sql_exist_arquivo = metodo_get(
            "SELECT id_arquivo FROM arquivos WHERE id_cliente = " . $_SESSION['company']['id'] . " AND id_modelo =  $modelo->id_modelo",
            'migracao'
        );

        $binds_arquivo = '';
        $dados_arquivos = [];

        if ($sql_exist_arquivo && isset($sql_exist_arquivo->id_arquivo)) {
            // Atualiza arquivo existente
            $sql = "UPDATE arquivos 
            SET nome_arquivo = ?, status = ? 
            WHERE id_cliente = ? AND id_modelo = ?";
            $binds_arquivo = 'ssii';
            $dados_arquivos = [
                $nomeArquivoCopiado,
                'E',
                $_SESSION['company']['id'],
                $modelo->id_modelo,
            ];
        } else {
            // Insere novo arquivo
            $sql = "INSERT INTO arquivos 
            (nome_arquivo, quantidade_registros, id_cliente, id_modelo, status) 
            VALUES (?, ?, ?, ?, ?)";
            $binds_arquivo = 'siiis';
            $dados_arquivos = [
                $nomeArquivoCopiado,
                $qntItems,
                $_SESSION['company']['id'],
                $modelo->id_modelo,
                'E'
            ];
        }

        insert_update($sql, $binds_arquivo, $dados_arquivos, 'migracao');

        // =================================================
        // =================================================

        return_api(200, '', [
            'arquivo_convertido' => $converted,
            'layout_colunas' => $layout_colunas,
            'modelo' => $modelo
        ]);
    }
}

function ftp_directory_exists($ftp_conn, $dir)
{
    $file_list = ftp_nlist($ftp_conn, $dir);
    return (count($file_list) > 0) ? true : false;
}

function salvaVinculacaoConvertidoLayout($data)
{
    $descricao_coluna = $data['descricao_coluna'];
    $id_layout_coluna = $data['id_layout_coluna'];
    $id_modelo = $data['id_modelo'];
    $id_concorrente = $data['id_concorrente'];

    $layout_coluna = metodo_get("SELECT * FROM layout_colunas WHERE id = $id_layout_coluna", 'migracao');

    // verifica se ja esta cadastrado
    $sql_exist = "SELECT id_modelo_coluna
    FROM modelos_colunas 
    -- WHERE id_layout_coluna = $id_layout_coluna 
    WHERE descricao_coluna = '$descricao_coluna' 
    AND id_modelo = $id_modelo 
    AND id_concorrente = $id_concorrente";

    $if_exist = metodo_get($sql_exist, 'migracao');

    $binds = '';
    $data = [];

    if (is_object($if_exist) && count((array)$if_exist) > 0) {
        // $sql_insert = "UPDATE modelos_colunas SET descricao_coluna = ? 
        // WHERE id_layout_coluna = ? 
        //     AND id_modelo = ? 
        //     AND id_concorrente = ?";
        // $binds = 'siii';
        // $data = [
        //     $descricao_coluna,
        //     $id_layout_coluna,
        //     $id_modelo,
        //     $id_concorrente
        // ];
        $sql_insert = "UPDATE modelos_colunas SET id_layout_coluna = ?
        WHERE id_modelo_coluna = ?";
        $binds = 'ii';
        $data = [
            $id_layout_coluna,
            $if_exist->id_modelo_coluna
        ];
    } else {
        $sql_insert = "INSERT INTO modelos_colunas (descricao_coluna, posicao_coluna, id_layout_coluna, id_modelo, id_concorrente, ativo) VALUES (?,?,?,?,?,?)";
        $binds = 'siiiii';
        $data = [
            $descricao_coluna,
            $layout_coluna->posicao,
            $id_layout_coluna,
            $id_modelo,
            $id_concorrente,
            1
        ];
    }
    insert_update($sql_insert, $binds, $data, 'migracao');

    return_api(200, '', []);
}

function atualizaColunaDePara($data)
{
    $id_layout_coluna = isset($data['id_layout_coluna']) ? (int)$data['id_layout_coluna'] : 0;
    $id_modelo = isset($data['id_modelo']) ? (int)$data['id_modelo'] : 0;
    $descricao_coluna = isset($data['descricao_coluna']) ? $data['descricao_coluna'] : '';
    $valores = isset($data['valores']) && is_array($data['valores']) ? $data['valores'] : [];

    if (!$id_layout_coluna || !$id_modelo || empty($descricao_coluna)) {
        return_api(400, 'Parâmetros inválidos', []);
        return;
    }

    $sql = "SELECT id_modelo_coluna FROM modelos_colunas WHERE id_modelo = $id_modelo AND id_layout_coluna = $id_layout_coluna";
    $modelo_coluna = metodo_get($sql, 'migracao');

    $sql = "SELECT 
                l.id, l.id_layout_coluna, m.descricao_coluna, l.conteudo_de, l.Conteudo_para_livre, l.substituir
            FROM layout_coluna_depara l
            LEFT JOIN modelos_colunas m ON l.id_layout_coluna = m.id_layout_coluna
            WHERE l.id_layout_coluna = $id_layout_coluna
                AND (" . ($modelo_coluna && isset($modelo_coluna->id_modelo_coluna) ? "l.id_modelo_coluna = {$modelo_coluna->id_modelo_coluna} OR " : "") . "l.id_modelo_coluna IS NULL)";
    $deparas = metodo_all($sql, 'migracao');

    $valores_transformados = [];
    foreach ($valores as $valor) {
        $valores_transformados[] = ConvertService::aplicarDePara($valor, $descricao_coluna, $deparas);
    }

    return_api(200, '', ['valores' => $valores_transformados]);
}

function EditVinculacaoArquivo($data)
{
    $id_modelo = $data['id_modelo'];
    $id_layout = $data['id_layout'];
    $id_concorrente = $data['id_concorrente'];
    $id_tipo_arquivo = $data['id_tipo_arquivo'];
    $id_arquivo = $data['id_arquivo'];



    $sql = "SELECT * FROM modelos m
    LEFT JOIN arquivos a ON m.id_modelo = a.id_modelo
    LEFT JOIN tipos_arquivos as t ON m.id_tipo_arquivo = t.id_tipo_arquivo
    LEFT JOIN concorrentes as c ON m.id_concorrente = c.id
    WHERE m.id_modelo = $id_modelo
    AND m.id_layout = $id_layout
    AND m.id_concorrente = $id_concorrente
    AND m.id_tipo_arquivo = $id_tipo_arquivo
    AND a.id_arquivo = $id_arquivo";
    $resultado = metodo_get($sql, "migracao");
    // dd($resultado);
    // ==========================================================================

    $sql_layout_colunas = "SELECT * 
    FROM layout_colunas 
    WHERE id_layout = $id_layout 
    ORDER BY posicao ASC";
    $layout_colunas = metodo_all($sql_layout_colunas, 'migracao');

    // ==========================================================================

    $sql_modelos_colunas = "SELECT * 
    FROM modelos_colunas 
    WHERE id_modelo = $resultado->id_modelo 
        AND id_concorrente = $resultado->id_concorrente";

    $modelos_colunas = metodo_all($sql_modelos_colunas, 'migracao');

    $tmpFile = __DIR__ . '/../../assets/' . $_SESSION['company']['nome'] . '/' . $resultado->nome_modelo . '/' . $resultado->id_modelo . '/' . $resultado->nome_arquivo;

    // var_dump($resultado->nome_arquivo);
    // var_dump(scandir(__DIR__ . '/../../assets/' . $_SESSION['company']['nome'] . '/' . $resultado->nome_modelo . '/' . $resultado->id_modelo . '/'));
    // die;

    $extension_file = pathinfo($resultado->nome_arquivo, PATHINFO_EXTENSION);

    if (!file_exists($tmpFile))
        die("Arquivo não encontrado: " . $tmpFile);

    $convert = new ConvertService($modelos_colunas);
    $converted = $convert->converter($tmpFile, $extension_file, $resultado->descr_tipo_arquivo);

    return_api(200, '', [
        'arquivo_convertido' => $converted,
        'modelos_colunas' => $modelos_colunas,
        'layout_colunas' => $layout_colunas,
        'modelo' => $resultado
    ]);
}

function removeVinculacaoConvertidoLayout($data)
{
    $id_layout_coluna = $data['id_layout_coluna'];
    $id_modelo = $data['id_modelo'];
    $descricao_coluna = $data['descricao_coluna'];
    $id_concorrente = $data['id_concorrente'];

    $sql = "DELETE FROM modelos_colunas 
            WHERE id_layout_coluna = ? 
                AND id_modelo = ? 
                AND descricao_coluna = ? 
                AND id_concorrente = ?";
    insert_update($sql, 'iisi', [
        $id_layout_coluna,
        $id_modelo,
        $descricao_coluna,
        $id_concorrente
    ], 'migracao');

    return_api(200);
}

function convertidos()
{
    $id_cliente = $_SESSION['company']['id'];

    $sql = "SELECT 
        a.id_arquivo, 
        a.nome_arquivo,
        a.status, 
        a.id_modelo,
        m.nome_modelo,
        c.nome AS nome_concorrente,
        l.nome AS nome_layout,
        t_a.descr_tipo_arquivo 
    FROM arquivos a
    LEFT JOIN modelos m ON m.id_modelo = a.id_modelo
    LEFT JOIN concorrentes c ON m.id_concorrente = c.id
    LEFT JOIN layout l ON m.id_layout = l.id
    LEFT JOIN tipos_arquivos t_a ON m.id_tipo_arquivo = t_a.id_tipo_arquivo
    WHERE a.id_cliente = $id_cliente
    GROUP BY a.id_arquivo
    ORDER BY a.id_arquivo DESC";

    $convertidos = metodo_all($sql, 'migracao');
    // dd($convertidos);
    return [
        'view' => 'conversao/convertidos',
        'data' => ['convertidos' => $convertidos],
        'function' => ''
    ];
}

function deparaIndex()
{
    $regras = [
        'id_layout_coluna' => ['required' => true, 'type' => 'int'],
        'id_modelo' => ['required' => true, 'type' => 'int'],
        'success' => ['required' => false, 'type' => 'int']
    ];
    $request = validateRequest($_GET, $regras);

    $id_layout_coluna = $request['dados']['id_layout_coluna'];
    $id_modelo = $request['dados']['id_modelo'];


    // echo "<pre>";
    // print_r($deparas);
    // echo "</pre>";

    $sql = "SELECT tipo FROM layout_colunas WHERE id = $id_layout_coluna";
    $tipo = metodo_get($sql, "migracao");

    $sql = "SELECT nome FROM concorrentes WHERE id = (SELECT id_concorrente FROM modelos WHERE id_modelo = $id_modelo)";
    $concorrente = metodo_get($sql, "migracao");

    $sql = "SELECT nome FROM layout WHERE id = (SELECT id_layout FROM layout_colunas WHERE id = $id_layout_coluna)";
    $layout = metodo_get($sql, "migracao");

    $sql = "SELECT id_modelo_coluna, descricao_coluna FROM modelos_colunas WHERE id_modelo = $id_modelo AND id_layout_coluna = $id_layout_coluna";
    $coluna = metodo_get($sql, "migracao");

    $sql = "SELECT * FROM layout_coluna_depara WHERE id_layout_coluna = $id_layout_coluna AND (id_modelo_coluna IS NULL OR id_modelo_coluna = " . $coluna->id_modelo_coluna . ")";
    $deparas = metodo_all($sql, "migracao");

    return [
        'view' => 'conversao/depara',
        'data' => [
            'deparas' => $deparas,
            'tipo' => $tipo->tipo,
            'id_layout_coluna' => $id_layout_coluna,
            'id_modelo' => $id_modelo,
            'concorrente' => $concorrente->nome,
            'layout' => $layout->nome,
            'descricao_coluna' => $coluna->descricao_coluna
        ],
        'function' => ''
    ];
}

function deletarTodosDepara()
{
    $id_layout_coluna = isset($_GET['id_layout_coluna']) ? $_GET['id_layout_coluna'] : '';
    $id_modelo = isset($_GET['id_modelo']) ? $_GET['id_modelo'] : '';
    $id = isset($_GET['id']) ? $_GET['id'] : '';

    if ($id_layout_coluna && $id_modelo) {
        $sql = "DELETE FROM layout_coluna_depara WHERE id_layout_coluna = ? AND (id_modelo_coluna IS NULL OR id_modelo_coluna = (SELECT id_modelo_coluna FROM modelos_colunas WHERE id_modelo = $id_modelo AND id_layout_coluna = $id_layout_coluna))";
        insert_update($sql, "i", [$id_layout_coluna], 'migracao');
    } else if ($id) {
        $sql = 'DELETE FROM layout_coluna_depara WHERE id = ?';
        insert_update($sql, "i", [$id], 'migracao');
    }

    return ['view' => '', 'data' => [], 'function' => "depara?id_layout_coluna=" . $id_layout_coluna . "&id_modelo=" . $id_modelo];
}

function deletarDepara()
{
    $id = isset($_GET["id"]) ? $_GET["id"] : "";

    if (!$id) {
        return_api(404, "ID não informado", []);
        return;
    }

    $sql = 'DELETE FROM layout_coluna_depara WHERE id = ?';
    insert_update($sql, "i", [$id], 'migracao');

    echo json_encode(['success' => true]);
    exit();
}

function deparaSalvar()
{

    $regras = [
        'id_layout_coluna' => ['required' => true, 'type' => 'int'],
        'id_modelo' => ['required' => true, 'type' => 'int'],
    ];
    $request = validateRequest($_POST, $regras);

    $id_layout_coluna = $request['dados']['id_layout_coluna'];
    $id_modelo = $request['dados']['id_modelo'];

    $sql = "SELECT id_modelo_coluna FROM modelos_colunas WHERE id_modelo = $id_modelo AND id_layout_coluna = $id_layout_coluna";
    $modelo_coluna = metodo_get($sql, 'migracao');

    // Atualiza os novos cards
    if (isset($_POST['novo_card_ids']) && is_array($_POST['novo_card_ids']) && count($_POST['novo_card_ids']) > 0) {
        foreach ($_POST['novo_card_ids'] as $i) {

            $de = isset($_POST["novo_depara_de_{$i}"]) ? $_POST["novo_depara_de_{$i}"] : '';
            $para = isset($_POST["novo_depara_para_{$i}"]) ? $_POST["novo_depara_para_{$i}"] : '';
            $substituir = isset($_POST["novo_substituir_{$i}"]) ? $_POST["novo_substituir_{$i}"] : '';
            $qualquer_concorrente = isset($_POST["novo_qualquer_concorrente_{$i}"]) ? true : false;

            if (!empty($de)) {
                if ($qualquer_concorrente) {
                    $sql = "INSERT INTO layout_coluna_depara (id_layout_coluna, conteudo_de, Conteudo_para_livre, substituir) VALUES (?,?,?,?)";
                    insert_update($sql, "isss", [
                        $id_layout_coluna,
                        $de,
                        $para,
                        $substituir ? '1' : '0'
                    ], 'migracao');
                } else {
                    $sql = "INSERT INTO layout_coluna_depara (id_layout_coluna, id_modelo_coluna, conteudo_de, Conteudo_para_livre, substituir) VALUES (?,?,?,?,?)";
                    insert_update($sql, "iisss", [
                        $id_layout_coluna,
                        $modelo_coluna->id_modelo_coluna,
                        $de,
                        $para,
                        $substituir ? '1' : '0'
                    ], 'migracao');
                }
            }
        }
    }

    // Atualiza os cards existentes
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'depara_de_') === 0) {
            $id = str_replace('depara_de_', '', $key);

            $de = $value;
            $para = isset($_POST["depara_para_{$id}"]) ? $_POST["depara_para_{$id}"] : '';
            $substituir = isset($_POST["substituir_{$id}"]) ? 1 : 0;
            $qualquer_concorrente = isset($_POST["qualquer_concorrente_{$id}"]) ? 1 : 0;

            if (!empty($de)) {
                $sql = "UPDATE layout_coluna_depara 
                        SET conteudo_de = ?, Conteudo_para_livre = ?, substituir = ?";
                $sql .= $qualquer_concorrente == 1 ? ", id_modelo_coluna = NULL" : ", id_modelo_coluna = " . $modelo_coluna->id_modelo_coluna;
                $sql .= " WHERE id = ?";

                insert_update(
                    $sql,
                    "ssii",
                    [
                        $de,
                        $para,
                        $substituir,
                        $id
                    ],
                    'migracao'
                );
            }
        }
    }

    $sql_modelo = "SELECT id_arquivo FROM arquivos WHERE id_modelo = $id_modelo and id_cliente =" . $_SESSION['company']['id'];
    $modelo = metodo_get($sql_modelo, 'migracao');
    return ['view' => '', 'data' => [], 'function' => "index?id_modelo=" . $id_modelo . "&id_arquivo=" . $modelo->id_arquivo];
}
