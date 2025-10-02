<?php
include_once(__DIR__ . '/../core/includes.php');

function downloadArquivo($data)
{
    $modelo = metodo_get("SELECT * FROM modelos WHERE id_modelo =" . $data['id_modelo'], 'migracao');

    if (!isset($modelo->id_modelo)) return return_api(200, 'Modelo não encontrado', []);

    $nome_arquivo_download = $_SESSION['company']['nome'] . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_" . ucfirst($data['tipo']) . ".xlsx";

    $arquivo = __DIR__ . "/../../assets/convertidos/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/{$modelo->id_modelo}/" . $nome_arquivo_download;

    // notdie($nome_arquivo_download);
    // dd(scandir(__DIR__ . "/../../assets/convertidos/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/{$modelo->id_modelo}/"));

    // Limpa buffers para evitar caracteres extras antes do download
    if (ob_get_level()) ob_end_clean();

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($nome_arquivo_download) . '"');
    header('Content-Transfer-Encoding: binary');
    // header('Expires: 0');
    // header('Cache-Control: must-revalidate');
    // header('Pragma: public');
    header('Content-Length: ' . filesize($arquivo));

    flush(); // força envio dos headers
    readfile($arquivo);
    exit; // garante que nada mais será executado após o download
}
