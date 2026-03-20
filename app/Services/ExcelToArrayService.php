<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__ . '/ChunkReadFilter.php';
require_once __DIR__ . '/ConvertWithPython.php';
class ExcelToArrayService
{
    private $php_office;
    private $depara_rules;

    function __construct($extensao, $depara_rules)
    {
        if ($extensao == 'xls')
            $this->php_office = new \PhpOffice\PhpSpreadsheet\Reader\Xls();

        if ($extensao == 'xlsx')
            $this->php_office = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        $this->depara_rules = $depara_rules;
    }

    public function convert($arquivo)
    {
        // if (filesize($arquivo) > 18 * 1024 * 1024) { //18MB
        return convertWithPython($arquivo, $this->depara_rules);
        // }

        // $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        // $reader->setReadDataOnly(true);

        // /**
        //  * 1) Descobrir o total de linhas SEM filtro
        //  */
        // $info = $reader->listWorksheetInfo($arquivo);
        // $highestRow = $info[0]['totalRows'];

        // /**
        //  * Validação correta
        //  */
        // if ($highestRow <= 1) {
        //     return [];
        // }

        // /**
        //  * 2) Agora sim aplicar o ChunkReadFilter
        //  */
        // $chunkSize = 500;
        // $filter = new ChunkReadFilter();
        // $reader->setReadFilter($filter);

        // $headers = [];
        // $data = [];

        // /**
        //  * Ler cabeçalho
        //  */
        // $filter->setRows(1, 1);
        // $spreadsheet = $reader->load($arquivo);
        // $sheet = $spreadsheet->getActiveSheet();

        // foreach ($sheet->getRowIterator(1, 1) as $row) {
        //     foreach ($row->getCellIterator() as $cell) {
        //         $headers[] = trim((string) $cell->getValue());
        //     }
        // }

        // $spreadsheet->disconnectWorksheets();
        // unset($spreadsheet);

        // /**
        //  * 2) Ler dados em chunks
        //  */
        // $startRow = 2;

        // while ($startRow <= $highestRow) {

        //     $filter->setRows($startRow, $chunkSize);
        //     $spreadsheet = $reader->load($arquivo);
        //     $sheet = $spreadsheet->getActiveSheet();

        //     foreach ($sheet->getRowIterator($startRow) as $row) {

        //         if ($row->getRowIndex() > $highestRow) {
        //             break;
        //         }

        //         $obj = [];
        //         $colIndex = 0;

        //         foreach ($row->getCellIterator() as $cell) {
        //             if (!isset($headers[$colIndex])) {
        //                 break;
        //             }

        //             $obj[$headers[$colIndex]] = $cell->getFormattedValue();
        //             $colIndex++;
        //         }

        //         if (!empty(array_filter($obj))) {
        //             $data[] = $obj;
        //         }
        //     }

        //     $spreadsheet->disconnectWorksheets();
        //     unset($spreadsheet);

        //     $startRow += $chunkSize;
        // }

        // return [$headers, $data];
        // --------------------------------------------------------
        // --------------------------------------------------------

        // $this->php_office->setReadDataOnly(true);
        // $spreadsheet = $this->php_office->load($arquivo);

        // $worksheet = $spreadsheet->getActiveSheet();
        // $data = $worksheet->toArray();
        // return $data;

        // // 1) Pega cabeçalhos da primeira linha
        // $headers = array_shift($data);

        // // 2) Cria caminhos absolutos a partir dos headers
        // $caminhos_absolutos = [];
        // foreach ($headers as $col) {
        //     if (!empty($col))
        //         $caminhos_absolutos[] = $col;
        // }
        // // 3) Cria array de dados
        // $children = [];
        // foreach ($data as $row) {
        //     $record = [];
        //     foreach ($headers as $i => $colName) {

        //         if (!empty($colName)) {
        //             $exploded = explode('.', $colName);
        //             $name_column = end($exploded);
        //             $valor = ConvertService::aplicarDePara($row[$i], $colName, $this->depara_rules);
        //             $record[$name_column] = $valor;
        //         }
        //     }
        //     $children[] = $record;
        // }

        return [$caminhos_absolutos, $children];
    }
}
