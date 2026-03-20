<?php

require_once __DIR__ . '/CsvToArrayService.php';

function convertWithPython(string $arquivo, array $depara_rules)
{

    $baseDir = realpath(__DIR__);

    if ($baseDir === false) {
        throw new RuntimeException("Invalid base directory");
    }

    $python  = "c:/Users/Administrador.VM003/AppData/Local/Programs/Python/Python314/python.exe"; // or full path if needed
    $script  = $baseDir . DIRECTORY_SEPARATOR . "pythonConvert.py";
    $input   = realpath($arquivo);

    if (!$input || !is_file($input)) {
        throw new RuntimeException("Invalid input file");
    }

    // Unique output file (prevents collision)
    $output = $baseDir . DIRECTORY_SEPARATOR . "dados.json";

    // Escape all arguments
    $cmd = sprintf(
        '%s %s %s %s json',
        escapeshellcmd($python),
        escapeshellarg($script),
        escapeshellarg($input),
        escapeshellarg($output)
    );

    exec($cmd . ' 2>&1', $cmdOutput, $exitCode);

    if ($exitCode !== 0) {
        throw new RuntimeException(
            "Python conversion failed: " . implode("\n", $cmdOutput)
        );
    }

    if (!is_file($output) || filesize($output) === 0) {
        throw new RuntimeException("CSV output not generated");
    }

    // Convert CSV to array
    $conversor = new JsonToArrayService('json', $depara_rules);
    $converted = $conversor->convert($output);

    // Cleanup
    unlink($output);

    return $converted;
}
