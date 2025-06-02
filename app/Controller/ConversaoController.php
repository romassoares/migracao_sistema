<?php
include_once(__DIR__ . '/../core/includes.php');
include_once(__DIR__ . '/../../routes/navigate.php');

function index()
{
    $id_concorrente = isset($_GET['id_concorrente']) ? $_GET['id_concorrente'] : '';
    $id_modelo = isset($_GET['id_modelo']) ? $_GET['id_modelo'] : '';

    $modelos_colunas = new stdClass();
    $modelo = new stdClass();

    if (!empty($id_modelo)) {
        $sql_modelo = "SELECT * FROM modelos WHERE id_modelo = $id_modelo";
        $modelo = metodo_get($sql_modelo, 'migracao');
        if (isset($modelo->id_modelo)) {
            $sql_modelos_colunas = "SELECT * FROM modelos_colunas WHERE id_modelo = $modelo->id_modelo AND id_concorrente = $id_concorrente";
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
        'tipos_arquivo' => $tipos_arquivo
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

        ini_set('memory_limit', '512M');

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

        $qntItems = count($converted) - 1;

        $dir_base = __DIR__ . '/../../assets/' . $_SESSION['company']['nome'] . '/' . $modelo->nome_modelo . '/' . $modelo->id_modelo . '/';

        if (!file_exists($dir_base))
            mkdir($dir_base, 0777, true);

        if (!copy($tmpFile, $dir_base . $nomeArquivo . '_' . date('i') . '_' . date('s') . '.' . $extension_file)) {
            return_api(404, 'Error ao salvar arquivo', []);
            return;
        }

        $nomeArquivoCopiado = $nomeArquivo . '_' . date('i') . '_' . date('s') . '.' . $extension_file;

        $sql = "INSERT INTO arquivos (nome_arquivo, quantidade_registros, id_cliente, id_modelo, status) VALUES (?, ?, ?, ?, ?)";

        insert_update($sql, "siiis", [
            $nomeArquivoCopiado,
            $qntItems,
            $modelo->id_concorrente,
            $modelo->id_modelo,
            'E'
        ], 'migracao');

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

    // verifica se ja esta cadastrado
    $sql_exist = "SELECT id_modelo_coluna
    FROM modelos_colunas 
    WHERE id_layout_coluna = $id_layout_coluna 
    AND id_modelo = $id_modelo 
    AND id_concorrente = $id_concorrente";

    $if_exist = metodo_get($sql_exist, 'migracao');

    $binds = '';
    $data = [];

    if (is_object($if_exist) && count((array)$if_exist) > 0) {
        $sql_insert = "UPDATE modelos_colunas SET descricao_coluna = ? 
        WHERE id_layout_coluna = ? 
            AND id_modelo = ? 
            AND id_concorrente = ?";
        $binds = 'siii';
        $data = [
            $descricao_coluna,
            $id_layout_coluna,
            $id_modelo,
            $id_concorrente
        ];
    } else {
        $sql_insert = "INSERT INTO modelos_colunas (descricao_coluna,id_layout_coluna,id_modelo,id_concorrente,ativo) VALUES (?,?,?,?,?)";
        $binds = 'siiii';
        $data = [
            $descricao_coluna,
            $id_layout_coluna,
            $id_modelo,
            $id_concorrente,
            1
        ];
    }
    insert_update($sql_insert, $binds, $data, 'migracao');

    return_api(200, '', []);
}

function EditVinculacaoArquivo($data)
{
    $id_modelo = $data['id_modelo'];
    $id_layout = $data['id_layout'];
    $id_concorrente = $data['id_concorrente'];
    $id_tipo_arquivo = $data['id_tipo_arquivo'];

    $sql = "SELECT * FROM modelos m
    LEFT JOIN arquivos a ON m.id_modelo = a.id_modelo
    LEFT JOIN tipos_arquivos as t ON m.id_tipo_arquivo = t.id_tipo_arquivo
    LEFT JOIN concorrentes as c ON m.id_concorrente = c.id
    WHERE m.id_modelo = $id_modelo
    AND m.id_layout = $id_layout
    AND m.id_concorrente = $id_concorrente
    AND m.id_tipo_arquivo = $id_tipo_arquivo";
    $resultado = metodo_get($sql, "migracao");

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

    $extension_file = pathinfo($resultado->nome_arquivo, PATHINFO_EXTENSION);

    if (!file_exists($tmpFile))
        die("Arquivo não encontrado: " . $tmpFile);

    $convert = new ConvertService();
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
