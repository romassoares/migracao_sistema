<?php
include_once(__DIR__ . '/../core/includes.php');

function baixarArquivo($data)
{
    $modelo = metodo_get("SELECT * FROM modelos WHERE id_modelo =" . $data['id_modelo'], 'migracao');

    if (!isset($modelo->id_modelo)) return return_api(200, 'Modelo não encontrado', []);

    $nome_arquivo_download = $_SESSION['company']['nome'] . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_" . ucfirst($data['tipo']) . ".xlsx";

    $arquivo = __DIR__ . "/../../assets/convertidos/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/" . $nome_arquivo_download;

    // if (!file_exists($arquivo)) return return_api(200, 'Arquivo não encontrado', []);

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($nome_arquivo_download) . '"');
    header('Content-Length: ' . filesize($arquivo));
    readfile($arquivo);
    return_api(200, '', []);
    // return;
}
