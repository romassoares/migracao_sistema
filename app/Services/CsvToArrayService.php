<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CsvToArrayService
{
    private $depara_rules;

    function __construct($extensao, $depara_rules)
    {
        $this->depara_rules = $depara_rules;
    }

    public function convert($arquivo)
    {
        $reader = IOFactory::createReaderForFile($arquivo);
        $spreadsheet = $reader->load($arquivo);

        $worksheet = $spreadsheet->getActiveSheet();

        $data = $worksheet->toArray();

        // 1) Pega cabeçalhos da primeira linha
        $headers = array_shift($data);

        // 2) Cria caminhos absolutos a partir dos headers
        $caminhos_absolutos = [];
        foreach ($headers as $col) {
            if (!empty($col))
                $caminhos_absolutos[] = $col;
        }

        // 3) Cria array de dados com aplicação do De/Para
        $children = [];
        foreach ($data as $row) {
            $record = [];
            foreach ($headers as $i => $colName) {
                if (!empty($colName)) {
                    $valor = ConvertService::aplicarDePara($row[$i], $colName, $this->depara_rules);
                    $record[$colName] = $valor;
                }
            }
            $children[] = $record;
        }

        return [$caminhos_absolutos, $children];
    }
}
