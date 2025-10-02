<?php
require_once __DIR__ . '/../core/includes.php';

set_time_limit(5000);

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

/**
 * Processes a data file, converts it, and generates an Excel spreadsheet.
 */
function processaArquivo($data)
{
    global $layout_colunas_depara, $spreadsheet, $spreadsheetCriticas, $spreadsheetCertos, $sheet, $sheetCriticas, $sheetCertos, $ifExistErro;

    if (ob_get_length()) ob_end_clean();

    ini_set('memory_limit', '512M');

    // Busca modelo
    $sql = "SELECT *, l.nome 
            FROM modelos AS m
            LEFT JOIN tipos_arquivos AS t ON m.id_tipo_arquivo = t.id_tipo_arquivo
            LEFT JOIN layout AS l ON m.id_layout = l.id
            LEFT JOIN concorrentes AS c ON m.id_concorrente = c.id
            LEFT JOIN arquivos AS a ON m.id_modelo = a.id_modelo
            WHERE m.id_modelo = " . intval($data['id_modelo']) . " and m.id_layout = " . intval($data['id_layout']) . " and m.id_concorrente = " . intval($data['id_concorrente']) . " and m.id_tipo_arquivo = " . intval($data['id_tipo_arquivo']);
    $modelo = metodo_get($sql, 'migracao');

    $modelo_colunas = metodo_all("SELECT * FROM modelos_colunas 
                                LEFT JOIN layout_colunas l_col on modelos_colunas.id_layout_coluna = l_col.id
                                  WHERE id_modelo = {$data['id_modelo']} 
                                  ORDER BY posicao_coluna", 'migracao');


    $result = metodo_all("SELECT 
                            l_col.id_layout,
                            l_col.id AS id_coluna,
                            l_col.posicao,
                            l_col.tipo,
                            l_col.obrigatorio,
                            l_col.nome_exibicao,
                            l_depara.id AS id_depara,
                            l_depara.conteudo_de,
                            l_depara.Conteudo_para_livre,
                            l_depara.substituir,
                            l_depara.ordem
                        FROM layout_colunas AS l_col
                        LEFT JOIN layout_coluna_depara AS l_depara 
                            ON l_col.id = l_depara.id_layout_coluna
                        WHERE l_col.id_layout = " . intval($data['id_layout']) . "
                        ORDER BY l_col.id, l_depara.ordem", 'migracao');

    foreach ($result as $row) {
        $id_coluna = $row['id_coluna'];
        $layout_colunas[$row['posicao']] = $row['nome_exibicao'];

        if (!isset($dados[$id_coluna])) {
            $dados[$id_coluna] = [
                'id_layout'   => $row['id_layout'],
                'posicao'     => $row['posicao'],
                'tipo'        => $row['tipo'],
                'obrigatorio' => $row['obrigatorio'],
                'depara'      => []
            ];
        }

        // Se existe registro de depara, adiciona no subarray
        if (!empty($row['conteudo_de'])) {
            $dados[$id_coluna]['depara'][] = [
                'conteudo_de'         => $row['conteudo_de'],
                'Conteudo_para_livre' => $row['Conteudo_para_livre'],
                'substituir'          => $row['substituir']
            ];
        }
    }

    $layout_colunas_depara = array_values($dados);

    $arquivo = metodo_get("SELECT * FROM arquivos 
                            WHERE id_modelo = " . intval($data['id_modelo']) . " 
                              AND id_cliente = " . $_SESSION['company']['id'] . " 
                              LIMIT 1", 'migracao');


    $arq_cli = "./assets/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/{$modelo->id_modelo}/{$arquivo->nome_arquivo}";
    if (!file_exists($arq_cli)) die('Arquivo não encontrado.');

    $extension_file = pathinfo($arquivo->nome_arquivo, PATHINFO_EXTENSION);

    $sql = "UPDATE arquivos SET status = ? WHERE id_cliente = ? and id_modelo = ?";
    insert_update($sql, "sii", ['P', $arquivo->id_cliente, $arquivo->id_modelo], 'migracao');

    // Converte
    $convert   = new ConvertService();
    $converted = $convert->converter($arq_cli, $extension_file, $modelo->descr_tipo_arquivo);

    $headers = $converted[0] ?? [];
    $dados   = $converted[1] ?? [];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $spreadsheetCriticas = new Spreadsheet();
    $sheetCriticas = $spreadsheetCriticas->getActiveSheet();

    $spreadsheetCertos = new Spreadsheet();
    $sheetCertos = $spreadsheetCertos->getActiveSheet();

    // Processa o array para excel
    $columnsUsed = setHeaderAndRetornColumns($modelo_colunas, $dados, $headers, $modelo,  $layout_colunas, $ifExistErro);

    // Pega a primeira coluna como chave para identificar duplicatas
    $colunaChave1 = $columnsUsed[0]['keys'] ?? null;
    // dd($colunaChave1);
    // ---------- Otimização: Processa grupos únicos para reduzir memória ----------
    $batchSize = 100; // Tamanho do lote de limpeza de memória
    $rowIndex = 2;
    $rowIndexCriticado = 2; // Começa a escrever na linha 2 (linha 1 é cabeçalho)
    $rowIndexCorreto = 2; // Começa a escrever na linha 2 (linha 1 é cabeçalho)
    $processedCount = 0;
    $gruposUnicos = [];

    $rowsRemoveCriticado = [];
    $rowsRemoveCorreto = [];

    // Agrupa dados únicos pela primeira coluna
    foreach ($dados as $key => $row) {
        $valor1 = $colunaChave1 ? getNestedValue($row, $colunaChave1) : '';
        $chaveGrupo = is_array($valor1) ? implode(',', $valor1) : (string)$valor1;
        if (!isset($gruposUnicos[$chaveGrupo . '_' . $key])) {
            $gruposUnicos[$chaveGrupo . '_' . $key] = $row;
        }
        $processedCount++;
        if ($processedCount % 1000 === 0) {
            gc_collect_cycles(); // Limpa memória a cada 1000 registros
        }
    }


    // ---------- Escreve os grupos únicos na planilha ----------
    $processedCount = 0;
    foreach ($gruposUnicos as $row) {
        $rowIndex = writeRowRecursive($row, $columnsUsed, $rowIndex, $ifExistErro);

        if ($ifExistErro) {
            $rowsRemoveCorreto[] = $rowIndex - 1;
        } else {
            $rowsRemoveCriticado[] = $rowIndex - 1;
        }

        $ifExistErro = false;

        $processedCount++;
        if ($processedCount % $batchSize === 0) {
            $spreadsheet->garbageCollect();
            gc_collect_cycles();
        }
    }

    foreach (array_reverse($rowsRemoveCriticado) as $rowNum) {
        $sheetCriticas->removeRow($rowNum);
    }

    foreach (array_reverse($rowsRemoveCorreto) as $rowNum) {
        $sheetCertos->removeRow($rowNum);
    }

    // ---------- Salva arquivo ----------
    $destinoDir = __DIR__ . "/../../assets/convertidos/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/" . $modelo->id_modelo . '/';
    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true)) {
        throw new \RuntimeException("Não foi possível criar pasta de destino: {$destinoDir}");
    }
    // Gera nome do arquivo com timestamp
    $caminhoFinal = $destinoDir . $_SESSION['company']['nome'] . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_Todos.xlsx";

    // Salva arquivo com todos os registros
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);
    $writer->save($caminhoFinal);

    // salva arquivo com as criticas;
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheetCriticas);
    $writer->setPreCalculateFormulas(false);
    $writer->save($destinoDir . $_SESSION['company']['nome'] . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_Criticados.xlsx");

    // salva arquivo com os erros
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheetCertos);
    $writer->setPreCalculateFormulas(false);
    $writer->save($destinoDir . $_SESSION['company']['nome'] . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_Corretos.xlsx");

    if (!filesize($caminhoFinal)) {
        throw new \RuntimeException("Falha ao salvar arquivo: {$caminhoFinal}");
    }

    unset($spreadsheet, $writer, $gruposUnicos);
    gc_collect_cycles();

    return return_api(200, '', $arquivo->status);
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
