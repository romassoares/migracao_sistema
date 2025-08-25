<?php
require_once __DIR__ . '/../core/includes.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


$db = new DB();

function index()
{
    $sql = 'SELECT 
            m.id_modelo, 
            m.nome_modelo, 
            l.nome as layout, 
            c.nome as concorrente, 
            ta.descr_tipo_arquivo as tipo_arquivo 
            FROM modelos m 
            LEFT JOIN layout l ON m.id_layout = l.id 
            LEFT JOIN concorrentes c ON m.id_concorrente = c.id 
            LEFT JOIN tipos_arquivos ta ON m.id_tipo_arquivo = ta.id_tipo_arquivo;';

    $modelo = metodo_all($sql, 'migracao');

    return ['view' => 'modelo/index', 'data' => ['modelos' => $modelo], 'function' => ''];
}

function create()
{
    $sql = 'SELECT * FROM layout WHERE ativo = 1 ORDER BY nome;';
    $layouts = metodo_all($sql, 'migracao');

    $sql = 'SELECT * FROM concorrentes ORDER BY nome;';
    $concorrentes = metodo_all($sql, 'migracao');

    $sql = 'SELECT id_tipo_arquivo AS id, descr_tipo_arquivo AS nome FROM tipos_arquivos;';
    $tipos_arquivos = metodo_all($sql, 'migracao');

    return ['view' => 'modelo/create', 'data' => ['layouts' => $layouts, 'concorrentes' => $concorrentes, 'tipos_arquivos' => $tipos_arquivos], 'function' => ''];
}

function store()
{
    $nome_modelo = trim(filter_input(INPUT_POST, 'nome_modelo', FILTER_SANITIZE_SPECIAL_CHARS));
    $id_layout = filter_input(INPUT_POST, 'layout', FILTER_SANITIZE_SPECIAL_CHARS);
    $id_concorrente = filter_input(INPUT_POST, 'concorrente', FILTER_SANITIZE_SPECIAL_CHARS);
    $id_tipo_arquivo = filter_input(INPUT_POST, 'tipo_arquivo', FILTER_SANITIZE_SPECIAL_CHARS);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $sql = 'INSERT INTO modelos (nome_modelo, id_layout, id_concorrente, id_tipo_arquivo, ativo) VALUES (?, ?, ?, ?, ?)';
    $params = [$nome_modelo, $id_layout, $id_concorrente, $id_tipo_arquivo, $ativo];

    insert_update($sql, 'siiii', $params, 'migracao');

    return ['view' => '', 'data' => [], 'function' => 'index'];
}

function detalhar()
{
    global $db;

    // fazer um select modelos_colunas baseado no id_layout_coluna e dps id_layout, e id_concorrente. com isso, saberemos quais colunas  mostrar
    $id_modelo = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);

    $sql = "SELECT 
            m.id_modelo, 
            m.nome_modelo, 
            m.id_layout, 
            m.id_concorrente, 
            m.id_tipo_arquivo, 
            l.nome as layout, 
            c.nome as concorrente, 
            ta.descr_tipo_arquivo as tipo_arquivo 
            FROM modelos m 
            LEFT JOIN layout l ON m.id_layout = l.id 
            LEFT JOIN concorrentes c ON m.id_concorrente = c.id 
            LEFT JOIN tipos_arquivos ta ON m.id_tipo_arquivo = ta.id_tipo_arquivo
            WHERE id_modelo = $id_modelo;";

    // $modelo = metodo_get($sql, 'migracao');
    $query = $db->connect('migracao')->query($sql);
    $modelo = $query->fetch_assoc();

    if (!$modelo) {
        die('Modelo não encontrado');
    }

    $sql = "SELECT 
            a.id_arquivo, 
            a.nome_arquivo,
            c.nome_cliente
            FROM arquivos a
            LEFT JOIN clientes c ON c.id_cliente = a.id_cliente
            WHERE a.id_cliente = ? AND a.id_modelo = ?
            ORDER BY id_arquivo
            ;";

    $stmt = $db->connect('migracao')->prepare($sql);
    $stmt->bind_param('ii', $_SESSION['company']['id'], $modelo['id_modelo']);

    if (!$stmt->execute()) {
        die('Failed to execute statement in insert update execute: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $arquivos = $result->fetch_all(MYSQLI_ASSOC);

    $sql = "SELECT 
            mc.id_modelo_coluna, 
            mc.descricao_coluna AS nome_modelo_coluna,
            lc.id AS id_layout_coluna,
            lc.nome_exibicao AS nome_layout_coluna,
            lc.posicao,
            lc.tipo,
            lc.obrigatorio 
            FROM modelos_colunas mc
            INNER JOIN layout_colunas lc ON mc.id_layout_coluna = lc.id 
            WHERE mc.id_concorrente = ? AND lc.id_layout = ? ";

    $stmt = $db->connect('migracao')->prepare($sql);
    $stmt->bind_param('ii', $modelo['id_concorrente'], $modelo['id_layout']);

    if (!$stmt->execute()) {
        die('Failed to execute statement in insert update execute: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $colunas = $result->fetch_all(MYSQLI_ASSOC);

    $dadosArquivo = lerArquivoCsv($colunas, $arquivos, $modelo);

    return ['view' => 'modelo/detalhar', 'data' => ['modelo' => $modelo, 'colunas' => $colunas, 'arquivos' => $arquivos, 'dadosArquivo' => $dadosArquivo], 'function' => ''];
}

function lerArquivoCsv($colunas, $arquivos, $modelo)
{
    $nome_colunas = [];
    foreach ($colunas as $index => $coluna) {
        $nome_colunas[$index]['nome_layout_coluna'] = $coluna['nome_layout_coluna'];
    }

    $caminho = __DIR__ . '/../../assets/' . $arquivos[0]['nome_cliente'] . '/' . $modelo['nome_modelo'] . '/' . $modelo['id_modelo'] . '/' . $arquivos[0]['nome_arquivo'];
    $dados = [];

    if (!file_exists($caminho)) {
        die('Arquivo não encontrado: ' . $caminho);
    }

    if (($handle = fopen($caminho, "r")) !== false) {
        // Lê o cabeçalho
        $cabecalho = fgetcsv($handle, 1000, ",");
        while (($linha = fgetcsv($handle, 1000, ",")) !== false) {
            $dados[] = array_combine($cabecalho, $linha);
        }
        fclose($handle);
    } else {
        die('Não foi possível abrir o arquivo.');
    }

    // Exemplo: retorna os dados lidos
    return $dados;
}

// function processaArquivo($data)
// {
//     if (ob_get_length()) ob_end_clean();

//     $sql = "SELECT *, l.nome 
//             FROM modelos AS m
//             LEFT JOIN tipos_arquivos AS t ON m.id_tipo_arquivo = t.id_tipo_arquivo
//             LEFT JOIN layout AS l ON m.id_layout = l.id
//             LEFT JOIN concorrentes AS c ON m.id_concorrente = c.id
//             WHERE id_modelo = " . intval($data['id_modelo']);
//     $modelo = metodo_get($sql, 'migracao');

//     $modelo_colunas = metodo_all("SELECT * FROM modelos_colunas 
//                                   WHERE id_modelo = {$data['id_modelo']} 
//                                   ORDER BY posicao_coluna", 'migracao');

//     $arquivo = metodo_get("SELECT * FROM arquivos 
//                             WHERE id_modelo = {$modelo->id_modelo} 
//                               AND id_cliente = {$modelo->id_concorrente} 
//                             LIMIT 1", 'migracao');

//     $arq_cli = "./assets/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/{$modelo->id_modelo}/{$arquivo->nome_arquivo}";
//     if (!file_exists($arq_cli)) die('Arquivo não encontrado.');

//     $extension_file = pathinfo($arquivo->nome_arquivo, PATHINFO_EXTENSION);

//     $convert   = new ConvertService();
//     $converted = $convert->converter($arq_cli, $extension_file, $modelo->descr_tipo_arquivo);

//     $headers = $converted[0] ?? [];
//     $dados   = $converted[1] ?? [];

//     $spreadsheet = new Spreadsheet();
//     $sheet = $spreadsheet->getActiveSheet();

//     // ---------- Mapeia colunas ----------
//     $columnsUsed = [];
//     $colNumber = 1;
//     foreach ($modelo_colunas as $mc) {
//         $descricao_coluna = $mc['descricao_coluna'];
//         if (in_array($descricao_coluna, $headers, true)) {
//             $keys = array_values(array_filter(explode('.', $descricao_coluna), 'strlen'));
//             $columnsUsed[] = [
//                 'header' => $descricao_coluna,
//                 'keys'   => $keys,
//                 'col'    => $colNumber
//             ];
//             setCellValueByColumnAndRow($sheet, $colNumber, 1, $descricao_coluna);
//             $colNumber++;
//         }
//     }

//     if (empty($columnsUsed)) {
//         if (ob_get_length()) ob_end_clean();
//         header('Content-Type: text/plain; charset=UTF-8');
//         echo "Nenhuma coluna do modelo corresponde aos headers do arquivo.";
//         exit;
//     }

//     // ---------- Write data ----------
//     $rowIndex = 2;

//     foreach ($dados as $row) {
//         $scalarValues = [];
//         $listValues = [];
//         $maxRows = 1;

//         // 1) Collect values and separate lists from scalars
//         foreach ($columnsUsed as $c) {
//             $vals = getNestedValues($row, $c['keys']);

//             if (empty($vals)) {
//                 $vals = [""];
//             }

//             // A 'list' is a value that returns multiple results
//             if (count($vals) > 1) {
//                 $listValues[$c['col']] = $vals;
//                 $maxRows = max($maxRows, count($vals));
//             } else {
//                 $scalarValues[$c['col']] = $vals[0];
//             }
//         }

//         // If there are no list values, we will just write one row.
//         if (empty($listValues)) {
//             $maxRows = 1;
//         }

//         // 2) Iterate based on the longest list and write rows
//         for ($i = 0; $i < $maxRows; $i++) {
//             $rowToWrite = [];
//             // Add scalar values (non-repeating)
//             foreach ($scalarValues as $col => $value) {
//                 $rowToWrite[$col] = $value;
//             }
//             // Add list values (one per iteration)
//             foreach ($listValues as $col => $values) {
//                 $rowToWrite[$col] = $values[$i] ?? ""; // Use empty string if index doesn't exist
//             }

//             // 3) Write to spreadsheet
//             foreach ($columnsUsed as $c) {
//                 $col = $c['col'];
//                 $valor = $rowToWrite[$col] ?? "";

//                 if (is_array($valor) || is_object($valor)) {
//                     $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
//                 } elseif (!is_string($valor)) {
//                     $valor = (string)$valor;
//                 }
//                 if (!mb_check_encoding($valor, 'UTF-8')) {
//                     $valor = mb_convert_encoding($valor, 'UTF-8', 'auto');
//                 }
//                 setCellValueByColumnAndRow($sheet, $col, $rowIndex + $i, $valor);
//             }
//         }

//         $rowIndex += $maxRows;
//     }

//     if (ob_get_length()) ob_end_clean();
//     header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
//     header('Content-Disposition: attachment;filename="arquivos.xlsx"');
//     header('Cache-Control: max-age=0');
//     $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
//     $writer->save('php://output');
//     exit;
// }

/**
 * Processes a data file, converts it, and generates an Excel spreadsheet.
 * The core logic is to flatten nested data structures, repeating scalar
 * values for each item in the longest nested list.
 *
 * @param array $data An associative array containing job data, including 'id_modelo'.
 */
function processaArquivo($data)
{
    $DEBUG = true;         // <= habilite passando ['debug'=>true]
    $DEBUG_MAX_ITEMS = 100;                  // limita quantos itens logar na aba DEBUG
    $debugRows = [];                         // linhas de debug para escrever na aba

    if (ob_get_length()) ob_end_clean();

    // Busca modelo
    $sql = "SELECT *, l.nome 
            FROM modelos AS m
            LEFT JOIN tipos_arquivos AS t ON m.id_tipo_arquivo = t.id_tipo_arquivo
            LEFT JOIN layout AS l ON m.id_layout = l.id
            LEFT JOIN concorrentes AS c ON m.id_concorrente = c.id
            WHERE id_modelo = " . intval($data['id_modelo']);
    $modelo = metodo_get($sql, 'migracao');

    $modelo_colunas = metodo_all("SELECT * FROM modelos_colunas 
                                  WHERE id_modelo = {$data['id_modelo']} 
                                  ORDER BY posicao_coluna", 'migracao');

    $arquivo = metodo_get("SELECT * FROM arquivos 
                            WHERE id_modelo = {$modelo->id_modelo} 
                              AND id_cliente = {$modelo->id_concorrente} 
                            LIMIT 1", 'migracao');

    $arq_cli = "./assets/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/{$modelo->id_modelo}/{$arquivo->nome_arquivo}";
    if (!file_exists($arq_cli)) die('Arquivo não encontrado.');

    $extension_file = pathinfo($arquivo->nome_arquivo, PATHINFO_EXTENSION);

    // Converte
    $convert   = new ConvertService();
    $converted = $convert->converter($arq_cli, $extension_file, $modelo->descr_tipo_arquivo);

    $headers = $converted[0] ?? [];
    $dados   = $converted[1] ?? [];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // ---------- Mapeia colunas ----------
    $columnsUsed = []; // ['header'=>..., 'keys'=>..., 'col'=>...]
    $colNumber = 1;
    foreach ($modelo_colunas as $mc) {
        $descricao_coluna = $mc['descricao_coluna'];
        if (in_array($descricao_coluna, $headers, true)) {
            $keys = array_values(array_filter(explode('.', $descricao_coluna), 'strlen'));
            $columnsUsed[] = [
                'header' => $descricao_coluna,
                'keys'   => $keys,
                'col'    => $colNumber
            ];
            setCellValueByColumnAndRow($sheet, $colNumber, 1, $descricao_coluna);
            $colNumber++;
        }
    }

    if (empty($columnsUsed)) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Nenhuma coluna do modelo corresponde aos headers do arquivo.";
        exit;
    }

    // ---------- Escreve dados ----------
    $rowIndex = 2; // primeira linha útil
    $itemIndex = 0;

    foreach ($dados as $row) {
        $itemIndex++;
        $scalarValues = []; // col => string
        $listValues   = []; // col => array
        $maxRows      = 1;

        $perItemDebug = [
            'item' => $itemIndex,
            'startRow' => $rowIndex,
            'columns' => [], // uma entrada por coluna
        ];

        // 1) Coleta e classifica valores
        foreach ($columnsUsed as $c) {
            $vals = getNestedValues($row, $c['keys']);    // sempre array
            if (!is_array($vals)) $vals = [$vals];
            // Evita JSON para listas simples de strings no getNestedValues (seu getNestedValues já trata; garantindo aqui)
            $vals = array_values($vals);

            $cls = classify_list_or_scalar($vals);

            if ($cls['type'] === 'list') {
                $listValues[$c['col']] = $cls['list'];
                $maxRows = max($maxRows, count($cls['list']));
            } else {
                $scalarValues[$c['col']] = $cls['scalar'];
            }

            // coleta debug por coluna
            if ($DEBUG && $itemIndex <= $DEBUG_MAX_ITEMS) {
                $perItemDebug['columns'][] = [
                    'col' => $c['col'],
                    'header' => $c['header'],
                    'keys' => implode('.', $c['keys']),
                    'rawCount' => $cls['rawCount'],
                    'nonEmptyCount' => $cls['nonEmptyCount'],
                    'type' => $cls['type'],
                    'scalar' => $cls['type'] === 'scalar' ? $cls['scalar'] : '',
                    'listLen' => $cls['type'] === 'list' ? count($cls['list']) : 0,
                    'rawSample' => implode(' | ', $cls['rawSample']),
                    'nonEmptySample' => implode(' | ', $cls['nonEmptySample']),
                ];
            }
        }

        // 2) Escreve linhas expandidas
        for ($i = 0; $i < $maxRows; $i++) {
            foreach ($columnsUsed as $c) {
                $col = $c['col'];
                if (isset($listValues[$col])) {
                    $valor = $listValues[$col][$i] ?? '';
                } else {
                    $valor = $scalarValues[$col] ?? '';
                }

                if (!is_string($valor)) $valor = norm_to_string($valor);
                if (!mb_check_encoding($valor, 'UTF-8')) {
                    $valor = mb_convert_encoding($valor, 'UTF-8', 'auto');
                }
                setCellValueByColumnAndRow($sheet, $col, $rowIndex + $i, $valor);

                // amostra do que foi escrito (para as 3 primeiras linhas do item)
                if ($DEBUG && $itemIndex <= $DEBUG_MAX_ITEMS && $i < 3) {
                    // localiza a entrada de coluna no perItemDebug para anexar 'writtenSample'
                    $idx = array_search($c['col'], array_column($perItemDebug['columns'], 'col'));
                    if ($idx !== false) {
                        if (empty($perItemDebug['columns'][$idx]['writtenSample'])) {
                            $perItemDebug['columns'][$idx]['writtenSample'] = [];
                        }
                        $perItemDebug['columns'][$idx]['writtenSample'][] =
                            "R" . ($rowIndex + $i) . "C" . $col . "=" . $valor;
                    }
                }
            }
        }

        // fecha item debug
        if ($DEBUG && $itemIndex <= $DEBUG_MAX_ITEMS) {
            $perItemDebug['maxRows'] = $maxRows;
            $debugRows[] = $perItemDebug;
        }

        $rowIndex += $maxRows;
    }

    // ---------- Aba DEBUG ----------
    if ($DEBUG) {
        $debugSheet = $spreadsheet->createSheet();
        $debugSheet->setTitle('DEBUG');

        // cabeçalho
        $hdr = [
            'Item',
            'StartRow',
            'MaxRows',
            'Col',
            'Header',
            'Keys',
            'Type',
            'Scalar',
            'ListLen',
            'RawCount',
            'NonEmptyCount',
            'RawSample',
            'NonEmptySample',
            'WrittenSample(1-3 linhas)'
        ];
        $colIdx = 1;
        foreach ($hdr as $h) {
            setCellValueByColumnAndRow($debugSheet, $colIdx++, 1, $h);
        }

        $r = 2;
        foreach ($debugRows as $itemDbg) {
            $item = $itemDbg['item'];
            $start = $itemDbg['startRow'];
            $max = $itemDbg['maxRows'] ?? 1;

            foreach ($itemDbg['columns'] as $cdbg) {
                $colIdx = 1;
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $item);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $start);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $max);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['col']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['header']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['keys']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['type']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['scalar']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['listLen']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['rawCount']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['nonEmptyCount']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['rawSample']);
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $cdbg['nonEmptySample']);
                $writtenSample = isset($cdbg['writtenSample']) ? implode(' | ', $cdbg['writtenSample']) : '';
                setCellValueByColumnAndRow($debugSheet, $colIdx++, $r, $writtenSample);
                $r++;
            }
        }
    }

    // ---------- Salvar arquivo no servidor (substitui o download) ----------
    $destinoDir = __DIR__ . '/app/assets'; // ajuste se quiser outro caminho
    if (!is_dir($destinoDir)) {
        if (!mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            // falha ao criar pasta
            throw new \RuntimeException("Não foi possível criar pasta de destino: {$destinoDir}");
        }
    }

    // nome do arquivo - usa timestamp para evitar sobrescrever
    $nomeArquivo = "arquivos_" . date("Ymd_His") . ".xlsx";
    $caminhoFinal = rtrim($destinoDir, '/\\') . DIRECTORY_SEPARATOR . $nomeArquivo;

    try {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($caminhoFinal);
    } catch (\Throwable $e) {
        // salva log de erro para investigação
        $errFile = rtrim($destinoDir, '/\\') . DIRECTORY_SEPARATOR . 'save_error_' . date('Ymd_His') . '.log';
        @file_put_contents($errFile, $e->__toString());
        throw $e; // rethrow para você ver/registrar no Sentry/laravel log, etc.
    }

    // verifica se arquivo foi criado com conteúdo
    if (!file_exists($caminhoFinal) || filesize($caminhoFinal) === 0) {
        $errFile = rtrim($destinoDir, '/\\') . DIRECTORY_SEPARATOR . 'save_error_zero_' . date('Ymd_His') . '.log';
        @file_put_contents($errFile, "Arquivo {$caminhoFinal} não existe ou está vazio após save()");
        throw new \RuntimeException("Falha ao salvar o arquivo em disco: {$caminhoFinal}");
    }

    // ---------- Salvar DEBUG separado ----------
    if ($DEBUG) {
        $debugJson = rtrim($destinoDir, '/\\') . DIRECTORY_SEPARATOR . 'debug_rows_' . date('Ymd_His') . '.json';

        $conteudo = !empty($debugRows)
            ? json_encode($debugRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : json_encode(['info' => 'DEBUG habilitado, mas $debugRows está vazio'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $ok = @file_put_contents($debugJson, $conteudo);
        if ($ok === false) {
            throw new \RuntimeException("Falha ao salvar JSON de debug em: {$debugJson}");
        }

        // também loga no error_log para confirmar execução
        error_log("DEBUG salvo em: " . $debugJson);
    }


    // Retorna o caminho final
    return $caminhoFinal;
}



// =============================

/**
 * Normaliza um valor para string (objetos/arrays em JSON).
 */
function norm_to_string($v): string
{
    if (is_array($v) || is_object($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
    return (string)$v;
}

/**
 * Decide se um array vindo do getNestedValues deve ser tratado como LISTA ou ESCALAR.
 * - Considera LISTA somente se tiver 2+ valores NÃO-VAZIOS.
 * - Se tiver 0 valores não-vazios => ESCALAR vazio.
 * - Se tiver 1 valor não-vazio => ESCALAR com esse valor.
 * Retorna:
 *  [
 *    'type' => 'scalar'|'list',
 *    'scalar' => string|null,
 *    'list' => string[]|null,
 *    'nonEmptyCount' => int,
 *    'rawCount' => int,
 *    'nonEmptySample' => string[],
 *    'rawSample' => string[],
 *  ]
 */
function classify_list_or_scalar(array $vals): array
{
    $vals = array_values($vals);
    $rawSample = array_map('norm_to_string', array_slice($vals, 0, 8));

    $nonEmpty = [];
    foreach ($vals as $v) {
        if (!($v === '' || $v === null)) {
            $nonEmpty[] = norm_to_string($v);
        }
    }
    $nonEmptyCount = count($nonEmpty);

    if ($nonEmptyCount === 0) {
        return [
            'type' => 'scalar',
            'scalar' => '',
            'list' => null,
            'nonEmptyCount' => 0,
            'rawCount' => count($vals),
            'nonEmptySample' => [],
            'rawSample' => $rawSample,
        ];
    }
    if ($nonEmptyCount === 1) {
        return [
            'type' => 'scalar',
            'scalar' => $nonEmpty[0],
            'list' => null,
            'nonEmptyCount' => 1,
            'rawCount' => count($vals),
            'nonEmptySample' => array_slice($nonEmpty, 0, 8),
            'rawSample' => $rawSample,
        ];
    }

    // 2+ valores não-vazios => LISTA
    return [
        'type' => 'list',
        'scalar' => null,
        'list' => array_values(array_map('norm_to_string', $nonEmpty)),
        'nonEmptyCount' => $nonEmptyCount,
        'rawCount' => count($vals),
        'nonEmptySample' => array_slice($nonEmpty, 0, 8),
        'rawSample' => $rawSample,
    ];
}
