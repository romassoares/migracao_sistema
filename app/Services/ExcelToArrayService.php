<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $spreadsheet = $this->php_office->load($arquivo);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();
        // return $data;

        // 1) Pega cabeçalhos da primeira linha
        $headers = array_shift($data);

        // 2) Cria caminhos absolutos a partir dos headers
        $caminhos_absolutos = [];
        foreach ($headers as $col) {
            if (!empty($col))
                $caminhos_absolutos[] = $col;
        }
        // 3) Cria array de dados
        $children = [];
        foreach ($data as $row) {
            $record = [];
            foreach ($headers as $i => $colName) {

                if (!empty($colName)) {
                    $exploded = explode('.', $colName);
                    $name_column = end($exploded);
                    $valor = ConvertService::aplicarDePara($row[$i], $colName, $this->depara_rules);
                    $record[$name_column] = $valor;
                }
            }
            $children[] = $record;
        }

        return [$caminhos_absolutos, $children];
    }
}
