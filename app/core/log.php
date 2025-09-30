<?php

$logFilePath = __DIR__ . '/logInsertValuesInColumnExcel.txt';

/**
 * Cria o arquivo de log caso não exista
 */
function createTextLog()
{
    global $logFilePath;

    // Garante que o diretório existe
    $dir_name = dirname($logFilePath);
    if (!is_dir($dir_name)) {
        mkdir($dir_name, 0777, true);
    }

    // Cria o arquivo vazio se não existir
    if (!file_exists($logFilePath)) {
        file_put_contents($logFilePath, "=== Log iniciado em " . date("Y-m-d H:i:s") . " ===" . PHP_EOL);
    }
}

/**
 * Escreve no log
 */
function writeInFileLog($text)
{
    global $logFilePath;

    $line = "[" . date("Y-m-d H:i:s") . "] " . $text . PHP_EOL;

    if (file_put_contents($logFilePath, $line, FILE_APPEND) === false) {
        die("Não foi possível escrever no arquivo de log: $logFilePath");
    }
}
