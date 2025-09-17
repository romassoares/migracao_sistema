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
    private $modelos_colunas;
    private $depara_rules = [];

    public function __construct($modelos_colunas = [])
    {
        $this->modelos_colunas = $modelos_colunas;
    }

    public function converter($tmpFile, $extension_file, $descr_tipo_arquivo)
    {
        if (is_array($this->modelos_colunas) && count($this->modelos_colunas) > 0) {
            $this->buscarDePara();
        }

        if ($descr_tipo_arquivo == $extension_file) {

            if ($extension_file == 'xml')
                $this->conversor_class = new XmlToArrayService();

            if ($extension_file == 'xlsx')
                $this->conversor_class = new ExcelToArrayService($extension_file, $this->depara_rules);

            if ($extension_file == 'csv')
                $this->conversor_class = new CsvToArrayService($extension_file);

            if ($extension_file == 'sql')
                $this->conversor_class = new SqlToArrayService($extension_file);

            if ($extension_file == 'json')
                $this->conversor_class = new JsonToArrayService($extension_file);

            $converted = $this->conversor_class->convert($tmpFile);

            return $converted;

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

    public static function aplicarDePara($valor, $colName, $deparas)
    {
        // Caso não tenha deparas, retorna o valor original
        if(empty($deparas)) return $valor;

        $valor_retorno = $valor;
        // Pega apenas os deparas que correspondem a coluna
        $itensFiltrados = array_filter($deparas, function($item) use ($colName) {
            return $item['descricao_coluna'] === $colName;
        });

        foreach ($itensFiltrados as $depara) {
            $conteudo_de = $depara['conteudo_de'];
            $conteudo_para = $depara['Conteudo_para_livre'];
            

            if($depara['substituir'] == 1 && preg_match(strtolower("/$conteudo_de/"), strtolower($valor))) {
                $valor_retorno = str_replace($conteudo_de, $conteudo_para, $valor);
            } else if ($depara['substituir'] == 0 && $conteudo_de == $valor) {
                $valor_retorno = $conteudo_para;
            }
        }

        return $valor_retorno;

        // print_r($itensFiltrados);
        // die();
    }

    public function buscarDePara()
    {
        if (!is_array($this->modelos_colunas) || count($this->modelos_colunas) === 0)
            return;

        $ids_modelos_colunas = array_column($this->modelos_colunas, 'id_modelo_coluna');
        $ids_layout_colunas = array_column($this->modelos_colunas, 'id_layout_coluna');

        $ids_modelo_string = implode(',', array_map('intval', $ids_modelos_colunas));
        $ids_layout_string = implode(',', array_map('intval', $ids_layout_colunas));

        if (empty($ids_layout_string)) return;

        $sql = "SELECT 
                    l.id, l.id_layout_coluna, m.descricao_coluna, l.conteudo_de, l.Conteudo_para_livre, l.substituir
                FROM
                    layout_coluna_depara l
                LEFT JOIN modelos_colunas m ON l.id_layout_coluna = m.id_layout_coluna
                WHERE 
                    l.id_layout_coluna IN ($ids_layout_string)
                    AND
                    (" . (!empty($ids_modelo_string) ? "l.id_modelo_coluna IN ($ids_modelo_string) OR " : "") . "l.id_modelo_coluna IS NULL)
                ORDER BY ordem ASC, id ASC";

        $regras = metodo_all($sql, 'migracao');
        $this->depara_rules = $regras;
    }
}
