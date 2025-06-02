<?php

class SqlToArrayService
{
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
                    $items[] = $linha;
                    $iniciou_insert = true;
                    continue;
                }


                if (strpos($linha, "insert into") === false && $iniciou_insert == true) {
                    $linha = str_replace(['`', "'", '"', "(", ")"], '', $linha);
                    if ($this->verifica_ja_existe_no_array($items, $linha)) {
                        $items[] = $linha;
                        $ultimo = substr($linha, -1);
                        if ($ultimo == ";")
                            $iniciou_insert = false;
                    }
                }
            }
            $i++;
            fclose($handle);

            foreach ($items as $key => $item) {
                $explode = [];
                $explode = explode(",", $item);

                for ($i = 0; count($explode) > $i; $i++)
                    $explode[$i] = preg_replace('/^"(.*)"$/', '$1', $explode[$i]);

                $items[$key] = $explode;
            }

            return $items;
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
