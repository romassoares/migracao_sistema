<?php

include_once __DIR__ . '/../core/functions.php';

class JsonToArrayService
{
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

        $conteudo = $this->converterObjetosParaArray($conteudo);

        $caminhos_absolutos = [];
        $this->monta_caminhos_absolutos_arquivo($conteudo, $caminhos_absolutos);
        $headers = array_unique($caminhos_absolutos);

        return [$headers, $conteudo];
    }

    private function converterObjetosParaArray($data)
    {
        if (is_object($data))
            $data = (array) $data;

        if (is_array($data)) {
            foreach ($data as $key => $value)
                $data[$key] = $this->converterObjetosParaArray($value);
        }

        return $data;
    }

    public function monta_caminhos_absolutos_arquivo($items, &$caminhos_absolutos, $prefix = '')
    {
        foreach ($items as $key => $value) {
            $segment = '';
            if (is_numeric($key)) {
                if (empty($prefix)) {
                    $segment = '';
                } else {
                    $segment = $prefix;
                }
            } else {
                if ($prefix === '') {
                    $segment = $key;
                } else {
                    $segment = $prefix . '.' . $key;
                }
            }
            $currentPrefix = $segment;

            if (is_array($value)) {
                $this->monta_caminhos_absolutos_arquivo($value, $caminhos_absolutos, $currentPrefix);
            } elseif (is_scalar($value) || is_null($value)) {
                $caminhos_absolutos[] = $currentPrefix;
            }
        }
    }
}
