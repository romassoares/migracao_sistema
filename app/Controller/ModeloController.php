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
    // dd($_SESSION['company'], $modelo['id_modelo']);
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
    // dd($arquivos);
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

    $dadosArquivo = lerArquivoCsv($colunas, $arquivos, $modelo);

    return ['view' => 'modelo/detalhar', 'data' => ['modelo' => $modelo, 'colunas' => $colunas, 'arquivos' => $arquivos, 'dadosArquivo' => $dadosArquivo], 'function' => ''];
}

function lerArquivoCsv($colunas, $arquivos, $modelo)
{
    $nome_colunas = [];
    foreach ($colunas as $index => $coluna) {
        $nome_colunas[$index]['nome_layout_coluna'] = $coluna['nome_layout_coluna'];
    }

    // dd($arquivos);
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

function processaArquivo($data)
{
    $sql = "SELECT *, l.nome FROM modelos AS m
        LEFT JOIN tipos_arquivos as t ON m.id_tipo_arquivo = t.id_tipo_arquivo
        LEFT JOIN layout as l ON m.id_layout = l.id
        LEFT JOIN concorrentes as c ON m.id_concorrente = c.id
        WHERE id_modelo =" . $data['id_modelo'];
    $modelo = metodo_get($sql, 'migracao');

    $modelo_colunas = metodo_all("SELECT * FROM modelos_colunas WHERE id_modelo = " . $data['id_modelo'] . " order by posicao_coluna", 'migracao');

    $layout_colunas = metodo_all("SELECT * FROM layout_colunas WHERE id_layout = $modelo->id_layout ORDER BY posicao", 'migracao');

    $arquivo = metodo_get("SELECT * FROM arquivos WHERE id_modelo = $modelo->id_modelo and id_cliente = $modelo->id_concorrente LIMIT 1", 'migracao');

    $arq_cli = "./assets/" . $_SESSION['company']['nome'] . '/' . $modelo->nome_modelo . '/' . $modelo->id_modelo . '/' . $arquivo->nome_arquivo;

    if (!file_exists($arq_cli))
        die('Arquivo não encontrado.');

    $extension_file = pathinfo($arquivo->nome_arquivo, PATHINFO_EXTENSION);

    $convert = new ConvertService();
    $converted = $convert->converter($arq_cli, $extension_file, $modelo->descr_tipo_arquivo);

    $headers = $converted[0];
    $data = $converted[1];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $spreadsheet_criticado = new Spreadsheet();
    $sheet_criticado = $spreadsheet->getActiveSheet();

    $spreadsheet_sem_critica = new Spreadsheet();
    $sheet_sem_critica = $spreadsheet->getActiveSheet();

    // Monta os cabeçalhos com base em modelo_colunas
    $colunas = [];
    $colIndex = 1; // Coluna A = 1
    $rowIndex = 1; // Linha 1 para cabeçalho

    foreach ($modelo_colunas as $coluna) {
        $descricao_coluna = $coluna['descricao_coluna'];

        // Verifica se está presente no header do arquivo convertido
        if (in_array($descricao_coluna, $headers)) {
            $colunas[] = $descricao_coluna;
            $cell = columnLetter($colIndex) . $rowIndex;
            $sheet->setCellValue($cell, $descricao_coluna);

            $sheet_criticado->setCellValue($cell, $descricao_coluna);
            $sheet_sem_critica->setCellValue($cell, $descricao_coluna);
            $colIndex++;
        }
    }

    // Preenche os dados
    $rowIndex = 2; // Começa na linha 2 para os dados

    foreach ((array) $data[0] as $key => $row) {
        $colIndex = 1;

        foreach ((array) $modelo_colunas as $coluna) {
            $valor = '';

            $valor = PercorreArrayDataConvertidoComModeloColuna($coluna, $row);

            var_dump($valor);
            die;

            // if (array_key_exists($key . "." . $coluna['descricao_coluna'], $row)) {
            //     var_dump($row, $coluna, 'S');
            // } else {
            //     var_dump($row, $coluna, 'N');
            // }
            // var_dump($row, $key);
            // die;
            // -------------------------------
            // --------------luiz-----------------
            // $valor = '';
            // $tmp_arrary = $row;

            // foreach ($explode as $i => $item) {
            //     if (is_array($tmp_arrary) && is_array($tmp_arrary[$item])) {
            //         if (array_key_exists(0, $tmp_arrary[$item])) {
            //             $tmp_arrary = $tmp_arrary[$item][0];
            //         } else {
            //             $tmp_arrary = $tmp_arrary[$item];
            //         }
            //     } else {
            //         $valor = $tmp_arrary[$item];
            //     }
            // }
            // -------------------------------
            // --------------luiz-----------------
            // if (array_key_exists($key . "." . $coluna['descricao_coluna'], $row)) {
            //     var_dump('achou');
            //     die();
            //     if (is_array($row[$coluna['descricao_coluna']]) && isset($row[$key . "." . $coluna['descricao_coluna']]['valor'])) {
            //         $valor = $row[$key . "." . $coluna['descricao_coluna']]['valor'];
            //     } else {
            //         $valor = $row[$key . "." . $coluna];
            //     }
            //     // }
            //     $row = $row[$explode[$i]];
            // } else {
            //     $row = '';
            //     break;
            // }

            $cell = columnLetter($colIndex) . $rowIndex;
            $sheet->setCellValue($cell, $valor);

            // criar função se tem valor e se é obrigatorio
            if (!empty($valor)) {
                $sheet_criticado->setCellValue($cell, $valor);
            } else {
            }
            $sheet_sem_critica->setCellValue($cell, $valor);

            $colIndex++;
        }
        $rowIndex++;
    }

    // Salvar em arquivo (opcional)
    // $writer = new Xlsx($spreadsheet);
    // $writer->save('relatorio.xlsx');

    // Envia o arquivo XLSX para download sem salvar no disco
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="arquivos.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function PercorreArrayDataConvertidoComModeloColuna($coluna, $row)
{

    $explode = explode('.', $coluna['descricao_coluna']);
    $tmp = $row;
    foreach ($explode as $key) {
        var_dump($key, $tmp);
        die;

        if (is_array($tmp) && array_key_exists($key, $tmp)) {
            $tmp = $tmp[$key];
        } else {
            // Se não encontrar, retorna vazio
            return '';
        }
    }
    // Se o valor final for array e tiver chave 'valor', retorna ela
    if (is_array($tmp) && isset($tmp['valor'])) {
        return $tmp['valor'];
    }
    return $tmp;
}

function columnLetter($colIndex)
{
    $dividend = $colIndex;
    $columnName = '';
    while ($dividend > 0) {
        $modulo = ($dividend - 1) % 26;
        $columnName = chr(65 + $modulo) . $columnName;
        $dividend = (int)(($dividend - $modulo) / 26);
    }
    return $columnName;
}
