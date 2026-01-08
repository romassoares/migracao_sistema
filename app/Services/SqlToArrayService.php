<?php

class SqlToArrayService
{
    private $depara_rules;

    function __construct($extensao, $depara_rules)
    {
        $this->depara_rules = $depara_rules;
    }

    public function convert($arquivo)
    {
        $items = [];
        $handle = fopen($arquivo, "r");
        if (!$handle) {
            dd("erro ao ler o arquivo");
        }

        $buffer = '';
        $iniciou_insert = false;

        while (($linha = fgets($handle)) !== false) {
            $linha = trim($linha);
            if ($linha === '') continue;

            if (stripos($linha, "insert into") !== false) {
                $iniciou_insert = true;
                $buffer = $linha;
            } elseif ($iniciou_insert) {
                $buffer .= ' ' . $linha;
            }

            if ($iniciou_insert && substr(trim($linha), -1) === ';') {
                // remove ';' e normaliza
                $comando = trim($buffer, " ;");
                $comando = preg_replace('/\s+/', ' ', $comando);

                // separa cabeçalho e valores
                if (preg_match('/insert into\s+[^()]+\(([^)]+)\)\s+values\s*\((.+)\)/is', $comando, $m)) {
                    $colunas = explode(',', $m[1]);
                    $valores = explode(',', $m[2]);

                    $colunas = array_map(function ($c) {
                        return trim(str_replace(['`', "'", '"'], '', $c));
                    }, $colunas);

                    $valores = array_map(function ($v) {
                        return trim(str_replace(['`', "'", '"'], '', $v));
                    }, $valores);

                    if (empty($items)) {
                        $items[] = implode(',', $colunas);
                    }

                    if ($this->verifica_ja_existe_no_array($items, implode(',', $valores))) {
                        $items[] = RemoveStrangeCharacter(implode(',', $valores));
                    }
                }

                $iniciou_insert = false;
                $buffer = '';
            }
        }

        fclose($handle);

        // ============================
        // Processa cabeçalho
        // ============================
        $headers = [];
        if (!empty($items[0])) {
            $headers = explode(",", $items[0]);
            array_walk($headers, fn(&$h) => $h = trim($h));
        }

        $children = [];
        for ($key = 1; $key < count($items); $key++) {
            $values = explode(",", $items[$key]);
            $record = [];

            foreach ($headers as $i => $colName) {
                if (!empty($colName) && isset($values[$i])) {
                    $value = trim($values[$i]);
                    $valor = ConvertService::aplicarDePara($value, $colName, $this->depara_rules);
                    $record[$colName] = $valor;
                }
            }

            if (!empty($record)) {
                $children[] = $record;
            }
        }

        return [$headers, $children];
    }



    public function verifica_ja_existe_no_array($array, $linha)
    {
        $result = array_filter($array, function ($item) use ($linha) {
            return strpos($item, $linha) !== false;
        });

        return empty($result) ? true : false;
    }
}
