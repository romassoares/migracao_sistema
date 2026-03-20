<?php
require_once __DIR__ . '/../core/includes.php';
require_once __DIR__ . "/ArquivoController.php";

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 4200);
    ini_set('session.cookie_lifetime', 4200);
    session_set_cookie_params(4200);
    session_start();
}

global $arquivoCriticado;

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
function processaArquivo($data, $update, $nomeCompany)
{
    global $layout_colunas_depara, $spreadsheet, $spreadsheetCriticas, $spreadsheetCertos, $sheet, $sheetCriticas, $sheetCertos, $ifExistErro;

    if (ob_get_length()) ob_end_clean();

    $update(0, "Iniciando...");


    $id_arquivo = $data['id_arquivo'];
    // Busca modelo

    $sql = "SELECT *, l.nome 
            FROM modelos AS m
            LEFT JOIN tipos_arquivos AS t ON m.id_tipo_arquivo = t.id_tipo_arquivo
            LEFT JOIN layout AS l ON m.id_layout = l.id
            LEFT JOIN concorrentes AS c ON m.id_concorrente = c.id
            LEFT JOIN arquivos AS a ON m.id_modelo = a.id_modelo
            WHERE m.id_modelo = " . intval($data['id_modelo']) . " and m.id_layout = " . intval($data['id_layout']) . " and m.id_concorrente = " . intval($data['id_concorrente']) . " and m.id_tipo_arquivo = " . intval($data['id_tipo_arquivo']);

    $modelo = metodo_get($sql, 'migracao');
    $update(5, "Carregando modelo...");
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
                        WHERE l_col.ativo = 1 AND l_col.id_layout = " . intval($data['id_layout']) . "
                        ORDER BY l_col.id, l_depara.ordem", 'migracao');
    $dados = [];
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
        $dados[$id_coluna]['depara'][] = [
            'conteudo_de'         => $row['conteudo_de'],
            'Conteudo_para_livre' => $row['Conteudo_para_livre'],
            'substituir'          => $row['substituir']
        ];
    }

    $layout_colunas_depara = array_values($dados);

    $arquivo = metodo_get("SELECT * FROM arquivos WHERE id_arquivo = $id_arquivo", 'migracao');

    $update(15, " Processar arquivo {$arquivo->nome_arquivo}...");
    // $update(15, " company nome {$_SESSION['company']['nome']}...");
    // $update(15, " modelo nome {$modelo->nome_modelo}...");
    // $update(15, " modelo id_modelo {$modelo->id_modelo}...");
    // $update(15, " arquivo nome_arquivo {$modelo->nome_arquivo}...");

    $arq_cli = "./assets/{$nomeCompany}/{$modelo->nome_modelo}/{$modelo->id_modelo}/{$arquivo->nome_arquivo}";
    // writeInFileLog("Arquivo cli.: " . $arq_cli);

    if (!file_exists($arq_cli)) {
        writeInFileLog("Arquivo não encontrado.");
        die('Arquivo não encontrado.');
    }
    $update(17, " Arquivo {$arquivo->nome_arquivo}... Encontrado. Convertendo...");

    $extension_file = pathinfo($arquivo->nome_arquivo, PATHINFO_EXTENSION);

    // Converte
    $convert   = new ConvertService();
    $converted = $convert->converter($arq_cli, $extension_file, $modelo->descr_tipo_arquivo, 'processaArquivo');

    $update(30, " Arquivo convertido. Gerando Excel...");
    $qntItems = count($converted[1]);

    $sql = "UPDATE arquivos SET quantidade_registros = ?, status = ? WHERE id_cliente = ? and id_modelo = ?";
    insert_update($sql, "isii", [$qntItems, 'P', $arquivo->id_cliente, $arquivo->id_modelo], 'migracao');

    $headers = $converted[0] ?? [];
    $dados   = $converted[1] ?? [];

    // writeInFileLog("pegou headers e dados");

    $destinoDir = __DIR__ . "/../../assets/convertidos/{$nomeCompany}/{$modelo->nome_modelo}/" . $modelo->id_modelo . '/';

    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true)) {
        throw new \RuntimeException("Não foi possível criar pasta de destino: {$destinoDir}");
    }
    // writeInFileLog("verificou que diretorio existe");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $arquivoCriticado = $destinoDir . $nomeCompany . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_Criticados.txt";

    createArquivoCriticadoLog($arquivoCriticado);
    // writeInFileLog("criou arquivo excel e txt criticado");

    $update(50, "criando headers...");
    // Processa o array para excel
    $columnsUsed = setHeaderAndRetornColumns($modelo_colunas, $dados, $headers, $modelo,  $layout_colunas, $ifExistErro);
    // var_dump($columnsUsed);
    // writeInFileLog("pegou colunas usadas");
    //     die;
    // Pega a primeira coluna como chave para identificar duplicatas
    $colunaChave1 = $columnsUsed[0]['keys'] ?? null;
    // ---------- Otimização: Processa grupos únicos para reduzir memória ----------
    $batchSize = 300; // Tamanho do lote de limpeza de memória
    $rowIndex = 2;
    $processedCount = 0;
    $gruposUnicos = [];
    $rowsRemoveCriticado = [];
    $rowsRemoveCorreto = [];

    // writeInFileLog("antes do foreach dados unicos");
    // Agrupa dados únicos pela primeira coluna
    foreach ($dados as $key => $row) {
        $colunaChave1 = is_array($colunaChave1) ? $colunaChave1 : [$colunaChave1];
        $valor1 = $colunaChave1 ? getNestedValue($row, $colunaChave1) : '';

        $chaveGrupo = is_array($valor1) ? implode(',', $valor1) : (string)$valor1;
        if (!isset($gruposUnicos[$chaveGrupo . '_' . $key])) {
            $gruposUnicos[$chaveGrupo . '_' . $key] = $row;
        }

        $processedCount++;
        if ($processedCount % 1000 === 0) {
            gc_collect_cycles();
        }
    }
    $update(60, "  Processando " . count($gruposUnicos) . " registros únicos...");
    // ---------- Escreve os grupos únicos na planilha ----------
    writeInFileLog("antes do foreach do recursivo");
    $processedCount = 0;
    // $pctSomaStatus = count($gruposUnicos) / 25;
    // $contagemStatus = 60;
    foreach ($gruposUnicos as $row) {

        $rowIndex = writeRowRecursive($row, $columnsUsed, $rowIndex, $ifExistErro);

        if ($ifExistErro) {
            $rowsRemoveCorreto[] = $rowIndex - 1;
        } else {
            $rowsRemoveCriticado[] = $rowIndex - 1;
        }

        $ifExistErro = false;

        $processedCount++;
        // if ($pctSomaStatus == $processedCount) {
        //     $update($contagemStatus++, "  Processando...");
        // }
        if ($processedCount % $batchSize === 0) {
            $spreadsheet->garbageCollect();
            gc_collect_cycles();
        }
    }
    writeInFileLog("depois do foreach do recursivo");
    $update(85, "  Finalizando planilha...");
    // ---------- Salva arquivo ----------
    // Gera nome do arquivo com timestamp
    $caminhoFinal = $destinoDir . $nomeCompany . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_Todos.xlsx";

    // Salva arquivo com todos os registros
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);
    $writer->save($caminhoFinal);
    $update(95, "limpando memoria.");
    // closeArquivoCriticadoLog();

    if (!filesize($caminhoFinal)) {
        throw new \RuntimeException("Falha ao salvar arquivo: {$caminhoFinal}");
    }

    unset($spreadsheet, $writer, $gruposUnicos);
    gc_collect_cycles();

    $update(100, "Processo concluído!");
    // return return_api(200, '', $arquivo->status);
    return;
}

function debugTime($msg = '')
{
    $mt = microtime(true);

    $time = date("H:i:s");

    $ms = sprintf("%04d", ($mt - floor($mt)) * 10000);

    echo "{$time}{$ms}  -->" . $msg . PHP_EOL;
}
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

function duplicar($data)
{
    $regras = [
        'id_modelo' => ['required' => true, 'type' => 'int'],
        'nome_modelo' => ['required' => true, 'type' => 'string']
    ];
    $request = validateRequest($data, $regras);

    $id_modelo = $request['dados']['id_modelo'];
    $nome_modelo = $request['dados']['nome_modelo'];

    $modelo = metodo_get("SELECT * FROM modelos WHERE id_modelo = $id_modelo", 'migracao');
    if (!$modelo || !$modelo->id_modelo) {
        return return_api(400, 'Modelo não encontrado');
    }

    $id_concorrente = $modelo->id_concorrente;
    $database = 'migracao';
    $db = new DB();
    $db->beginTransaction($database);

    try {

        // =================================================================

        $sql = "INSERT INTO modelos (nome_modelo, id_layout, id_concorrente, ativo, id_tipo_arquivo) VALUES (?,?,?,?,?)";
        $novo_id_modelo = insert_update($sql, 'siisi', [
            $nome_modelo,
            $modelo->id_layout,
            $modelo->id_concorrente,
            $modelo->ativo,
            $modelo->id_tipo_arquivo,
        ], $database);

        // =================================================================

        $sql = "INSERT INTO modelos_colunas (id_modelo, id_layout_coluna, posicao_coluna, formato_data, ativo, id_concorrente, descricao_coluna)
                SELECT $novo_id_modelo AS id_modelo, id_layout_coluna, posicao_coluna, formato_data, ativo, id_concorrente, descricao_coluna
                FROM modelos_colunas
                WHERE id_modelo = $id_modelo
                  AND id_concorrente = $id_concorrente";
        metodo_exec($sql, $database);

        // =================================================================

        $sql_deparas_ant = "SELECT lcd.*, mc.id_layout_coluna
            FROM modelos_colunas AS mc
            INNER JOIN layout_coluna_depara AS lcd
                ON mc.id_layout_coluna = lcd.id_layout_coluna
            WHERE mc.id_modelo = $id_modelo
            AND mc.id_concorrente = $id_concorrente
        ";
        $deparas_ant = metodo_all($sql_deparas_ant, $database);

        $sql_deparas_novo = "SELECT * FROM modelos_colunas AS ml
            WHERE ml.id_modelo = $novo_id_modelo
            AND ml.id_concorrente = $id_concorrente
        ";
        $deparas_novo = metodo_all($sql_deparas_novo, $database);

        $sql_dup_deparas = "INSERT INTO layout_coluna_depara (
            id_layout_coluna,
            id_modelo_coluna,
            conteudo_de,
            id_layout_coluna_conteudos,
            `Conteudo_para_livre`,
            substituir,
            ordem
        ) VALUES ";

        $valores = [];

        foreach ($deparas_ant as $antigo) {
            foreach ($deparas_novo as $novo) {
                // Matching rule: same layout column
                if ($antigo['id_layout_coluna'] == $novo['id_layout_coluna']) {

                    $valores[] = sprintf(
                        "(%d, %d, %s, %s, %s, %s, %s)",
                        $antigo['id_layout_coluna'],
                        $novo['id_modelo_coluna'],
                        $antigo['conteudo_de'] !== null ? ("'" . addslashes($antigo['conteudo_de']) . "'") : 'NULL',
                        $antigo['id_layout_coluna_conteudos'] !== null ? $antigo['id_layout_coluna_conteudos'] : 'NULL',
                        $antigo['Conteudo_para_livre'] !== null ? ("'" . addslashes($antigo['Conteudo_para_livre']) . "'") : 'NULL',
                        $antigo['substituir'] !== null ? $antigo['substituir'] : 'NULL',
                        $antigo['ordem'] !== null ? $antigo['ordem'] : 'NULL'
                    );
                }
            }
        }

        if (!empty($valores)) {
            $sql_dup_deparas .= implode(", ", $valores);
            metodo_exec($sql_dup_deparas, $database);
        } else {
            return return_api(200, 'Nenhum depara duplicado (sem correspondências encontradas)', [
                'modelo' => $novo_id_modelo
            ]);
        }


        // metodo_exec($sql_dup_deparas, $database);

        // =================================================================
        $modeloDuplicado = metodo_get("SELECT * FROM modelos WHERE id_modelo = $novo_id_modelo", $database);

        $db->commit($database);
        return return_api(200, 'Modelo duplicado com sucesso', ['modelo' => $modeloDuplicado]);
    } catch (\Throwable $th) {
        $db->rollBack($database);
        die("Erro ao duplicar modelo: " . $th->getMessage());
    }
}

function createArquivoCriticadoLog($dir_name)
{
    global $arquivoCriticado;
    $arquivoCriticado = $dir_name;

    file_put_contents($arquivoCriticado, '');
}

function getModeloColunas($data)
{
    $regras = [
        'id_modelo' => ['required' => true, 'type' => 'int']
    ];
    $request = validateRequest($data, $regras);
    $id_modelo = $request['dados']['id_modelo'];

    $sql = "SELECT 
            lc.id AS id_layout_coluna,
            lc.nome_exibicao AS nome_layout_coluna
            FROM modelos_colunas mc
            LEFT JOIN layout_colunas lc ON mc.id_layout_coluna = lc.id 
            WHERE mc.id_modelo = $id_modelo";

    $colunas = metodo_all($sql, 'migracao');

    if ($colunas || $colunas['id_modelo']) {
        return return_api(200, 'Modelos encontrados', ['colunas' => $colunas]);
    }
}
