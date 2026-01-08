<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function debugLog($msg)
{
    file_put_contents(
        __DIR__ . "/worker_debug.log",
        "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n",
        FILE_APPEND
    );
}

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    debugLog("PHP ERROR: [$errno] $errstr - $errfile:$errline");
});
set_exception_handler(function ($e) {
    debugLog("UNCAUGHT EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString());
});
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        debugLog("FATAL ERROR: " . print_r($error, true));
    }
});

/* ===============================================================
   PEGANDO PARÂMETROS — AGORA NA ORDEM CORRETA
   =============================================================== */

$jobId = $argv[1] ?? null;
$baseDir = rtrim($argv[2] ?? "", "\\/") . DIRECTORY_SEPARATOR;

$sessionRaw = $argv[3] ?? "";
$_SESSION = json_decode(base64_decode($sessionRaw), true);

$data = json_decode(base64_decode($argv[4] ?? ""), true);

// debugLog("jobId: " . var_export($jobId, true));
// debugLog("baseDir recebido: " . var_export($baseDir, true));
// debugLog("_SESSION decodificada: " . print_r($_SESSION, true));

if (!$jobId) {
    // debugLog("ERRO: jobId não informado. Encerrando.");
    die("ERRO: jobId não informado.\n");
}


$statusFile = $baseDir . '/Jobs/status/status.json';

// debugLog("statusFile path: " . $statusFile);

$dirStatus = dirname($statusFile);
// debugLog("Diretório do status.json: " . $dirStatus);
// debugLog("Diretório existe? " . (is_dir($dirStatus) ? "SIM" : "NÃO"));
// debugLog("Diretório é gravável? " . (is_writable($dirStatus) ? "SIM" : "NÃO"));
// debugLog("Arquivo status.json já existe? " . (file_exists($statusFile) ? "SIM" : "NÃO"));

require_once $baseDir . '/core/includes.php';

function setProgress($file, $pct, $msg = "")
{
    // debugLog("Atualizando status para {$pct}% - {$msg}");

    @file_put_contents(
        $file,
        json_encode([
            "status"   => $pct >= 100 ? "finished" : "processing",
            "progress" => $pct,
            "message"  => $msg
        ], JSON_PRETTY_PRINT)
    );
}

/* ===============================================================
   INÍCIO DO PROCESSAMENTO
   =============================================================== */

setProgress($statusFile, 0, "Iniciando...");

require $baseDir . '/Controller/ModeloController.php';

// debugLog("ModeloController carregado. Iniciando processaArquivo.");

/* ===============================================================
   EXECUÇÃO DA ROTINA PRINCIPAL
   =============================================================== */
processaArquivo(
    $data,
    function ($pct, $msg = "") use ($statusFile) {
        setProgress($statusFile, $pct, $msg);
    }
);
