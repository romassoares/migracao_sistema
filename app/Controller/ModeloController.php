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

/**
 * Processes a data file, converts it, and generates an Excel spreadsheet.
 */
function processaArquivo($data)
{
    global $layout_colunas_depara;

    if (ob_get_length()) ob_end_clean();

    // Busca modelo
    $sql = "SELECT *, l.nome 
            FROM modelos AS m
            LEFT JOIN tipos_arquivos AS t ON m.id_tipo_arquivo = t.id_tipo_arquivo
            LEFT JOIN layout AS l ON m.id_layout = l.id
            LEFT JOIN concorrentes AS c ON m.id_concorrente = c.id
            WHERE m.id_modelo = " . intval($data['id_modelo']) . " and m.id_layout = " . intval($data['id_layout']) . " and m.id_concorrente = " . intval($data['id_concorrente']) . " and m.id_tipo_arquivo = " . intval($data['id_tipo_arquivo']);

    $modelo = metodo_get($sql, 'migracao');

    $layout_colunas_depara = metodo_all("SELECT l_depara.conteudo_de,l_depara.Conteudo_para_livre,l_depara.substituir, l_col_conteu.conteudo AS conteudo_layout, l_col_conteu.descricao as descricao_coluna
                                FROM layout_colunas AS l_col
                                LEFT JOIN layout_coluna_conteudos AS l_col_conteu USING(id) 
                                LEFT JOIN layout_coluna_depara AS l_depara ON l_col_conteu.id = l_depara.id_layout_coluna
                                WHERE id_layout =" . intval($data['id_layout']) . " 
                                ORDER BY posicao", 'migracao');

    $modelo_colunas = metodo_all("SELECT * FROM modelos_colunas 
                                  WHERE id_modelo = {$data['id_modelo']} 
                                  ORDER BY posicao_coluna", 'migracao');

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

    // Processa o array para excel
    processaArrayForExcel($modelo_colunas, $dados, $headers, $spreadsheet, $sheet, $modelo);


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
