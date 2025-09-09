<?php
require __DIR__ . "/../../vendor/autoload.php";

include_once __DIR__ . '/ExcelToArrayService.php';
include_once __DIR__ . '/XmlToArrayService.php';
include_once __DIR__ . '/CsvToArrayService.php';
include_once __DIR__ . '/SqlToArrayService.php';
include_once __DIR__ . '/JsonToArrayService.php';

class ConvertService
{

    private $conversor_class;
    public function converter($tmpFile, $extension_file, $descr_tipo_arquivo)
    {

        if ($descr_tipo_arquivo == $extension_file) {

            if ($extension_file == 'xml')
                $this->conversor_class = new XmlToArrayService();

            if ($extension_file == 'xlsx')
                $this->conversor_class = new ExcelToArrayService($extension_file);

            if ($extension_file == 'csv')
                $this->conversor_class = new CsvToArrayService($extension_file);

            if ($extension_file == 'sql')
                $this->conversor_class = new SqlToArrayService($extension_file);

            if ($extension_file == 'json')
                $this->conversor_class = new JsonToArrayService($extension_file);

            return $this->conversor_class->convert($tmpFile);
        } else {
            die("arquivo não é do tipo esperado no modelo");
        }
        return ['status' => 'ok', 'mensagem' => 'Arquivo convertido com sucesso.'];
    }

    public function trata_objeto($data, &$record)
    {
        $data = (object) $data;
        foreach ($data as $prop => $value) {
            $attributesTag = [];
            if ((string) $value->attributes() !== '') {
                foreach ($value->attributes() as $key => $att)
                    $attributesTag[$key] = (string) $att;
            }


            if ($value->count() > 0) {
                $record[$prop] = [];
                $this->trata_objeto($value, $record[$prop]);
            } else {
                if (count($attributesTag) > 0) {
                    $record[$prop] = [$attributesTag, (string) $value];
                } else {
                    $record[$prop] = (string) $value;
                }
            }
        }
    }
}
