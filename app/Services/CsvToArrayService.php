<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CsvToArrayService
{
    public function convert($arquivo)
    {
        $reader = IOFactory::createReaderForFile($arquivo);
        $spreadsheet = $reader->load($arquivo);

        $worksheet = $spreadsheet->getActiveSheet();

        $data = $worksheet->toArray();
        return $data;
    }
}
