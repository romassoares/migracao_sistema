<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelToArrayService
{
    private $php_office;

    function __construct($extensao)
    {
        if ($extensao == 'xls')
            $this->php_office = new \PhpOffice\PhpSpreadsheet\Reader\Xls();

        if ($extensao == 'xlsx')
            $this->php_office = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    }

    public function convert($arquivo)
    {
        $spreadsheet =  $this->php_office->load($arquivo);

        $worksheet = $spreadsheet->getActiveSheet();

        $data = $worksheet->toArray();
        return $data;
    }
}
