<?php

include_once __DIR__ . '/../core/functions.php';

class JsonToArrayService
{
    private $depara_rules;

    function __construct($extensao, $depara_rules)
    {
        $this->depara_rules = $depara_rules;
    }

    public function convert($arquivo)
    {

        $handle = fopen($arquivo, "r");

        if (!$handle) {
            die("Erro ao abrir o arquivo: $arquivo");
        }

        // lê o arquivo inteiro de uma vez
        $conteudo = fread($handle, filesize($arquivo));
        fclose($handle);

        // decodifica o JSON
        $dados = json_decode($conteudo, true); // true para retornar array associativo

        if (json_last_error() !== JSON_ERROR_NONE) {
            die("Erro ao decodificar JSON: " . json_last_error_msg());
        }

        $conteudo = $dados;

        if (!$conteudo || !is_array($conteudo)) {
            return [];
        }

        $conteudo = $this->converterObjetosParaArray($conteudo, '');

        $caminhos_absolutos = [];
        monta_caminhos_absolutos_arquivo($conteudo, $caminhos_absolutos);

        $headers = array_unique($caminhos_absolutos);


        return [$headers, $conteudo];
    }

    private function converterObjetosParaArray($data, $caminho = '')
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (is_array($data)) {
            $resultado = [];
            foreach ($data as $key => $value) {
                $novo_caminho = empty($caminho) ? $key : $caminho . '.' . $key;
                
                if (is_array($value) || is_object($value)) {
                    $resultado[$key] = $this->converterObjetosParaArray($value, $novo_caminho);
                } else {
                    // Aplica De/Para apenas em valores folha (não arrays/objetos)
                    $valor = ConvertService::aplicarDePara((string)$value, $novo_caminho, $this->depara_rules);
                    $resultado[$key] = $valor;
                }
            }
            return $resultado;
        }

        // Se for um valor simples e tiver caminho (é uma folha)
        if (!empty($caminho)) {
            return ConvertService::aplicarDePara((string)$data, $caminho, $this->depara_rules);
        }

        return $data;
    }
}
