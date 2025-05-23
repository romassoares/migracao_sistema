<?php
include_once(__DIR__ . '/../core/includes.php');
include_once(__DIR__ . '/../../routes/navigate.php');

function index()
{
    $sql_concorrentes = 'SELECT * FROM concorrentes';
    $concorrentes = metodo_all($sql_concorrentes, 'migracao');


    $sql_layouts = 'SELECT * FROM layout';
    $layouts = metodo_all($sql_layouts, 'migracao');

    $sql_tipo_arquivos = 'SELECT * FROM tipos_arquivos';
    $tipos_arquivo = metodo_all($sql_tipo_arquivos, 'migracao');

    return ['view' => 'conversao/index', 'data' => ['layouts' => $layouts, 'concorrentes' => $concorrentes, 'tipos_arquivo' => $tipos_arquivo], 'function' => ''];
}

function uploadArquivo()
{
    $id_modelo = $_POST['modelo_id'];
    $file = $_FILES;

    if (isset($file['arquivo'])) {

        $sql = "SELECT *, t.descr_tipo_arquivo FROM modelos AS m
        LEFT JOIN tipos_arquivos as t ON m.id_tipo_arquivo = t.id_tipo_arquivo
        LEFT JOIN layout as l ON m.id_layout = l.id
        LEFT JOIN concorrentes as c ON m.id_concorrente = c.id
        WHERE id_modelo = $id_modelo";
        $modelo = metodo_get($sql, 'migracao');

        $sql_layout_colunas = "SELECT * FROM layout_colunas WHERE id_layout = " . $modelo->id_layout . " ORDER BY posicao ASC";
        $layout_colunas = metodo_all($sql_layout_colunas, 'migracao');

        ini_set('memory_limit', '512M');

        // $connection = ftp_connect('ftp.localhost');
        // $login = ftp_login($connection, $ftp_user_name, $ftp_user_pass);

        // if (!$connection || !$login)
        //     die('Connection attempt failed!');

        $basename = basename($file['arquivo']['name']);
        // dd($basename);
        // $remoteFile = '';
        // if ($_SESSION['ambiente'] == 'D') {
        //     $remoteFile =  'migracao/' . $basename;
        // } else {
        //     $remoteFile =  './' . $basename;
        // }
        // notdie($layout_colunas);
        $tmpFile = $_FILES['arquivo']['tmp_name'];

        $extension_file = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);

        // dd($tmpFile, $basename, $modelo);
        // if (ftp_put($connection, $remoteFile, $tmpFile, FTP_BINARY)) {
        //     ftp_close($connection);

        // $xml = simplexml_load_file($tmpFile);

        $convert = new ConvertService();

        $converted = $convert->converter($tmpFile, $extension_file, $modelo->descr_tipo_arquivo);

        return_api(200, '', ['arquivo_convertido' => $converted]);
        // processar por tipo de arquivo
        // }
        // }
    }
}

function ftp_directory_exists($ftp_conn, $dir)
{
    $file_list = ftp_nlist($ftp_conn, $dir);
    return (count($file_list) > 0) ? true : false;
}

// function update()
// {
//     $regras = [
//         'id' => ['required' => true, 'type' => 'int'],
//         'nome' => ['required' => true, 'type' => 'string']
//     ];
//     $request = validateRequest($_POST, $regras);

//     $dados = $request['dados'];

//     $sql = "UPDATE layout SET nome = ? WHERE id = ? ORDER BY nome ASC";

//     insert_update($sql, "si", [$dados['nome'], $dados['id']], 'migracao');

//     return ['view' => '', 'data' => [], 'function' => 'index'];
// }
