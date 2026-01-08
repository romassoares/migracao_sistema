<?php
include_once(__DIR__ . '/../core/includes.php');

function lerArquivo()
{
    $id_modelo = $_POST['modelo_id'];
    $file = $_FILES;

    $sql = "SELECT *, l.nome FROM modelos AS m
        LEFT JOIN tipos_arquivos as t ON m.id_tipo_arquivo = t.id_tipo_arquivo
        LEFT JOIN layout as l ON m.id_layout = l.id
        LEFT JOIN concorrentes as c ON m.id_concorrente = c.id
        WHERE id_modelo = $id_modelo";
    $modelo = metodo_get($sql, 'migracao');

    $sql_layout_colunas = "SELECT * FROM layout_colunas WHERE id_layout = " . $modelo->id_layout . " ORDER BY posicao ASC";
    $layout_colunas = metodo_all($sql_layout_colunas, 'migracao');

    $modelo_colunas = metodo_all("SELECT * FROM modelos_colunas 
                                LEFT JOIN layout_colunas l_col on modelos_colunas.id_layout_coluna = l_col.id
                                  WHERE id_modelo = {$id_modelo} 
                                  ORDER BY posicao_coluna", 'migracao');
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

    // converte arquivo para array
    $convert = new ConvertService();
    $converted = $convert->converter($tmpFile, $extension_file, $modelo->descr_tipo_arquivo);

    return_api(200, '', [
        'arquivo_convertido' => $converted,
        'modelo' => $modelo,
        'layout_colunas' => $layout_colunas,
        'modelos_colunas' => $modelo_colunas
    ]);
}

function downloadArquivo($data)
{
    $modelo = metodo_get("SELECT * FROM modelos WHERE id_modelo =" . $data['id_modelo'], 'migracao');

    if (!isset($modelo->id_modelo)) {
        return return_api(200, 'Modelo não encontrado', []);
    }

    // Define o nome do arquivo para download
    $nome_arquivo_download = $_SESSION['company']['nome'] . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_" . ucfirst($data['tipo']);
    $nome_arquivo_download .= ($data['tipo'] == 'Criticados') ? '.txt' : '.xlsx';

    // Caminho físico do arquivo
    $arquivo = __DIR__ . "/../../assets/convertidos/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/{$modelo->id_modelo}/" . $nome_arquivo_download;

    // Verifica se o arquivo existe
    if (!file_exists($arquivo)) {
        return return_api(404, 'Arquivo não encontrado', []);
    }

    // Limpa buffers para evitar caracteres extras antes do download
    if (ob_get_level()) ob_end_clean();

    // Define o tipo de conteúdo de acordo com o tipo de arquivo
    if ($data['tipo'] == 'Criticados') {
        // TXT
        header('Content-Type: text/plain; charset=UTF-8');
    } else {
        // Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename="' . basename($nome_arquivo_download) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($arquivo));

    // Envia o arquivo para o cliente
    flush();
    readfile($arquivo);
    exit;
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

        // =================================================
        // =================================================

        //definição do diretório base
        $dir_base = __DIR__ . '/../../assets/' . $_SESSION['company']['nome'] . '/' . $modelo->nome_modelo . '/' . $modelo->id_modelo . '/';

        //verifica se o diretório existe, se não existir cria
        if (!file_exists($dir_base))
            mkdir($dir_base, 0777, true);

        $nomeArquivoCopiado = $nomeArquivo . '_' . date('i') . '_' . date('s') . '.' . $extension_file;
        //verifica se o arquivo já existe, se existir adiciona a data e hora
        if (!copy($tmpFile, $dir_base . $nomeArquivoCopiado)) {
            return_api(404, 'Error ao salvar arquivo', []);
            return;
        }


        // =================================================
        // =================================================

        // Buscar se já existe o arquivo
        // $sql_exist_arquivo = metodo_get(
        //     "SELECT id_arquivo FROM arquivos WHERE id_cliente = " . $_SESSION['company']['id'] . " AND id_modelo =  $modelo->id_modelo",
        //     'migracao'
        // );

        $binds_arquivo = '';
        $dados_arquivos = [];

        // if ($sql_exist_arquivo && isset($sql_exist_arquivo->id_arquivo)) {
        //     // Atualiza arquivo existente
        //     $sql = "UPDATE arquivos 
        //     SET nome_arquivo = ?, status = ? 
        //     WHERE id_cliente = ? AND id_modelo = ?";
        //     $binds_arquivo = 'ssii';
        //     $dados_arquivos = [
        //         $nomeArquivoCopiado,
        //         'E',
        //         $_SESSION['company']['id'],
        //         $modelo->id_modelo,
        //     ];
        // } else {
        // Insere novo arquivo
        $sql = "INSERT INTO arquivos 
            (nome_arquivo, id_cliente, id_modelo, status) 
            VALUES (?, ?, ?, ?)";
        $binds_arquivo = 'siis';
        $dados_arquivos = [
            $nomeArquivoCopiado,
            $_SESSION['company']['id'],
            $modelo->id_modelo,
            'E'
        ];
        // }

        $id_arquivo = insert_update($sql, $binds_arquivo, $dados_arquivos, 'migracao');

        // =================================================
        // =================================================

        return_api(200, '', ['id_arquivo' => $id_arquivo]);
    }
}
