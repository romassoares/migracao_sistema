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
        $iniciou_insert = false;

        $handle = fopen($arquivo, "r");
        if ($handle) {
            $i = 0;
            while (($linha = fgets($handle)) !== false) {
                $linha = strtolower(trim($linha));

                if (strpos($linha, "insert into") !== false && empty($items[0]) && $i == 0) {
                    $linha = strstr($linha, '(');
                    $linha = str_replace('values', '', $linha);
                    $linha = str_replace(['`', "'", '"', "(", ")"], '', $linha);
                    $items[] = RemoveStrangeCharacter($linha);
                    $iniciou_insert = true;
                    continue;
                }


                if (strpos($linha, "insert into") === false && $iniciou_insert == true) {
                    $linha = str_replace(['`', "'", '"', "(", ")"], '', $linha);
                    if ($this->verifica_ja_existe_no_array($items, $linha)) {
                        $items[] = RemoveStrangeCharacter($linha);
                        $ultimo = substr($linha, -1);
                        if ($ultimo == ";")
                            $iniciou_insert = false;
                    }
                }
            }
            $i++;
            fclose($handle);

            // Processa os headers (primeira linha)
            $headers = [];
            if (!empty($items[0])) {
                $headers = explode(",", $items[0]);
                array_walk($headers, function (&$header) {
                    $header = trim($header);
                });
            }

            // Cria caminhos absolutos
            $caminhos_absolutos = [];
            foreach ($headers as $col) {
                if (!empty($col)) {
                    $caminhos_absolutos[] = $col;
                }
            }

            // Processa os valores com De/Para
            $children = [];
            for ($key = 1; $key < count($items); $key++) {
                $values = explode(",", $items[$key]);
                $record = [];

                foreach ($headers as $i => $colName) {
                    if (!empty($colName) && isset($values[$i])) {
                        // Remove aspas dos valores
                        $value = preg_replace('/^"(.*)"$/', '$1', trim($values[$i]));
                        // Aplica as regras de De/Para
                        $valor = ConvertService::aplicarDePara($value, $colName, $this->depara_rules);
                        $record[$colName] = $valor;
                    }
                }

                if (!empty($record)) {
                    $children[] = $record;
                }
            }
            // notdie($children);
            // die;

            return [$caminhos_absolutos, $children];
        } else {
            dd("error ao ler o arquivo");
        }
    }
    public function verifica_ja_existe_no_array($array, $linha)
    {
        $result = array_filter($array, function ($item) use ($linha) {
            return strpos($item, $linha) !== false;
        });

        return empty($result) ? true : false;
    }
}
