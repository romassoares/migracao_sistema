<?php
require_once __DIR__ . '/../core/includes.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
            id_arquivo, 
            nome_arquivo 
            FROM arquivos 
            WHERE id_cliente = ?  AND id_modelo = ?
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
            WHERE mc.id_concorrente = ? AND lc.id_layout = ?
            ;";

    // $colunas = metodo_all($sql, 'migracao');

    $stmt = $db->connect('migracao')->prepare($sql);
    $stmt->bind_param('ii', $modelo['id_concorrente'], $modelo['id_layout']);
    
    if (!$stmt->execute()) {
        die('Failed to execute statement in insert update execute: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $colunas = $result->fetch_all(MYSQLI_ASSOC);

    $dadosArquivo = lerArquivoCsv($colunas);

    return ['view' => 'modelo/detalhar', 'data' => ['modelo' => $modelo, 'colunas' => $colunas, 'arquivos' => $arquivos, 'dadosArquivo' => $dadosArquivo ], 'function' => ''];
}

function lerArquivoCsv($colunas)
{
    $nome_colunas = [];
    foreach ($colunas as $index => $coluna) {
        $nome_colunas[$index]['nome_layout_coluna'] = $coluna['nome_layout_coluna'];
    }

    $caminho = __DIR__ . '/../../uploads/imoveis.csv';
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

function gerarArquivoGeral()
{
    $csvPath = __DIR__ . '/../../uploads/imoveis.csv';

    if (!file_exists($csvPath)) {
        die('Arquivo CSV não encontrado.');
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    if (($handle = fopen($csvPath, "r")) !== false) {
        $row = 1;
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            foreach ($data as $col => $value) {
                $sheet->setCellValue([$col + 1, $row], $value);
            }
            $row++;
        }
        fclose($handle);
    } else {
        die('Não foi possível abrir o arquivo CSV.');
    }

    // Envia o arquivo XLSX para download sem salvar no disco
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="imoveis.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}