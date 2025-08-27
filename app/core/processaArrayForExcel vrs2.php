<?php

/**
 * Orquestra o processo de exportação de dados de arrays aninhados para um arquivo Excel (.xlsx).
 *
 * Esta função é o ponto de entrada principal. Ela mapeia as colunas, escreve os
 * dados na planilha de forma recursiva e salva o arquivo final no servidor.
 *
 * @param array $modelo_colunas  Array que define as colunas desejadas na planilha.
 * @param array $dados           Array de dados complexos/aninhados a serem exportados.
 * @param array $headers         Cabeçalhos das colunas disponíveis nos dados de origem.
 * @param object $spreadsheet    Instância do objeto PhpSpreadsheet.
 * @param object $sheet          Instância da aba da planilha (Worksheet).
 * @param object $modelo         Objeto contendo metadados, como o nome do modelo para a pasta de destino.
 * @throws \RuntimeException Se a pasta de destino não puder ser criada ou o arquivo não for salvo.
 */
function processaArrayForExcel($modelo_colunas, $dados, $headers, $spreadsheet, $sheet, $modelo)
{
    // ---------- Mapeia colunas e define cabeçalhos ----------
    $i = 1;
    $columnsUsed = [];
    foreach ($modelo_colunas as $mc) {
        $descricao_coluna = $mc['descricao_coluna'];
        if (in_array($descricao_coluna, $headers, true)) {
            $keys = array_filter(explode('.', $descricao_coluna), 'strlen');
            $columnsUsed[] = [
                'header' => $descricao_coluna,
                'keys'   => array_values($keys),
                'col'    => $i,
            ];
            setCellValueByColumnAndRow($sheet, $i, 1, $descricao_coluna);
            // var_dump($mc,  $i);
            $i++;
        }
    }

    if (!$columnsUsed) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: text/plain; charset=UTF-8');
        exit("Nenhuma coluna do modelo corresponde aos headers do arquivo.");
    }

    // ---------- Identificar as duas primeiras colunas do modelo ----------
    $colunaChave1 = $columnsUsed[0]['keys'] ?? null;
    $colunaChave2 = $columnsUsed[1]['keys'] ?? null;

    // ---------- Otimização: Processa em lotes e limpa memória ----------
    $batchSize = 100;
    $rowIndex = 2;
    $processedCount = 0;

    $gruposUnicos = [];
    foreach ($dados as $row) {
        // Extrai os valores das duas primeiras colunas
        // echo "DEBUG keys: ";
        // print_r($keys);
        // echo "DEBUG row: ";
        // print_r($row);
        // die();
        $valor1 = $colunaChave1 ? getNestedValue($row, $colunaChave1) : '';
        $valor2 = $colunaChave2 ? getNestedValue($row, $colunaChave2) : '';
        $chaveGrupo = (string)$valor1 . '|' . (string)$valor2;

        if (!isset($gruposUnicos[$chaveGrupo])) {
            $gruposUnicos[$chaveGrupo] = $row;
        }

        $processedCount++;
        if ($processedCount % 1000 === 0) {
            gc_collect_cycles();
        }
    }

    // Processa os grupos únicos
    $processedCount = 0;
    foreach ($gruposUnicos as $row) {
        $rowIndex = writeRowRecursive($sheet, $row, $columnsUsed, $rowIndex);
        $processedCount++;
        if ($processedCount % $batchSize === 0) {
            $spreadsheet->garbageCollect();
            gc_collect_cycles();
        }
    }

    // ---------- Salvar arquivo ----------
    $destinoDir = __DIR__ . "/../../assets/convertidos/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/";
    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true)) {
        throw new \RuntimeException("Não foi possível criar pasta de destino: {$destinoDir}");
    }

    $caminhoFinal = $destinoDir . "arquivos_" . date("Ymd_His") . ".xlsx";

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);
    $writer->save($caminhoFinal);

    if (!filesize($caminhoFinal)) {
        throw new \RuntimeException("Falha ao salvar arquivo: {$caminhoFinal}");
    }

    unset($spreadsheet, $writer, $gruposUnicos);
    gc_collect_cycles();

    return $caminhoFinal;
}

/**
 * Escreve dados recursivamente na planilha, lidando com arrays aninhados e listas.
 *
 * Esta é a função central que "achata" a estrutura de dados complexa. Ela se chama
 * recursivamente para processar objetos aninhados e usa o produto cartesiano para
 * desdobrar listas em múltiplas linhas.
 *
 * @param object $sheet        A aba da planilha.
 * @param array $data          O array de dados no nível de recursão atual.
 * @param array $columnsUsed   Array de colunas que serão escritas.
 * @param int $rowIndex        O índice da linha atual na planilha (1-based).
 * @param array $prefix        O caminho de chaves para o nível atual (e.g., ['cliente', 'endereco']).
 * @param array $fixedValues   Valores de colunas do nível superior que devem ser mantidos nas linhas filhas.
 * @return int O novo índice da linha após a escrita.
 */
function writeRowRecursive($sheet, $data, $columnsUsed, $rowIndex, $prefix = [], $fixedValues = [], &$lastRowValues = [])
{
    foreach ($columnsUsed as $c) {
        if (
            count($c['keys']) === count($prefix) + 1 &&
            array_slice($c['keys'], 0, count($prefix)) === $prefix
        ) {
            $key = end($c['keys']);
            $valor = $data[$key] ?? "";
            if (!is_array($valor)) {
                $fixedValues[$c['col']] = safeToString($valor);
            }
        }
    }


    $listas = [];
    $objetos = [];
    foreach ($data as $k => $v) {
        if (is_array($v) && array_keys($v) === range(0, count($v) - 1)) {
            $listas[$k] = $v;
        } elseif (is_array($v)) {
            $objetos[$k] = $v;
        }
    }

    if ($listas) {
        $combos = cartesianProduct($listas);

        foreach ($combos as $combo) {
            $rowVals = $fixedValues;
            $filhoEscreveu = false;

            foreach ($combo as $chaveLista => $valorLista) {
                if (is_scalar($valorLista) || $valorLista === null) {
                    $col = findColumnIndex($columnsUsed, array_merge($prefix, [$chaveLista]));
                    if ($col) {
                        $rowVals[$col] = safeToString($valorLista);
                    }
                } else {
                    $antes = $rowIndex;
                    $rowIndex = writeRowRecursive(
                        $sheet,
                        $valorLista,
                        $columnsUsed,
                        $rowIndex,
                        array_merge($prefix, [$chaveLista]),
                        $rowVals,
                        $lastRowValues
                    );
                    if ($rowIndex > $antes) $filhoEscreveu = true;
                }
            }

            if ($filhoEscreveu) {
                continue;
            }

            // ✅ Verifica duplicação antes de escrever
            if ($rowVals !== $lastRowValues) {
                foreach ($rowVals as $col => $valor) {
                    setCellValueByColumnAndRow($sheet, $col, $rowIndex, $valor);
                }
                $lastRowValues = $rowVals;
                $rowIndex++;
            }
        }
        return $rowIndex;
    }

    if ($objetos) {
        $antes = $rowIndex;
        foreach ($objetos as $chObj => $objVal) {
            $rowIndex = writeRowRecursive(
                $sheet,
                $objVal,
                $columnsUsed,
                $rowIndex,
                array_merge($prefix, [$chObj]),
                $fixedValues,
                $lastRowValues
            );
        }
        if ($rowIndex > $antes) {
            return $rowIndex;
        }
        $rowVals = $fixedValues;
        foreach ($objetos as $chObj => $objVal) {
            fillScalarValues($objVal, $columnsUsed, array_merge($prefix, [$chObj]), $rowVals);
        }

        // ✅ Verifica duplicação antes de escrever
        if ($rowVals !== $lastRowValues) {
            foreach ($rowVals as $col => $valor) {
                setCellValueByColumnAndRow($sheet, $col, $rowIndex, $valor);
            }
            $lastRowValues = $rowVals;
            $rowIndex++;
        }
        return $rowIndex;
    }

    // caso final (sem listas/objetos)
    if ($fixedValues !== $lastRowValues) {
        foreach ($fixedValues as $col => $valor) {
            setCellValueByColumnAndRow($sheet, $col, $rowIndex, $valor);
        }
        $lastRowValues = $fixedValues;
        $rowIndex++;
    }

    return $rowIndex;
}




/**
 * Agrega valores escalares mapeáveis de toda a subárvore em $fixedValues,
 * sem escrever linhas. É usada para coletar todos os valores simples de um objeto
 * aninhado que não possui listas.
 *
 * @param array $data           O array de dados para buscar valores.
 * @param array $columnsUsed    Array de colunas que serão escritas.
 * @param array $prefix         O caminho de chaves para o nível atual.
 * @param array $fixedValues    Array de referência para onde os valores serão adicionados.
 */
function fillScalarValues($data, $columnsUsed, $prefix, &$fixedValues)
{
    foreach ($columnsUsed as $c) {
        if (
            count($c['keys']) === count($prefix) + 1 &&
            array_slice($c['keys'], 0, count($prefix)) === $prefix
        ) {
            $key = end($c['keys']);
            if (isset($data[$key]) && !is_array($data[$key])) {
                $fixedValues[$c['col']] = safeToString($data[$key]);
            }
        }
    }

    // Chama-se recursivamente para processar objetos aninhados dentro do nível atual.
    foreach ($data as $k => $v) {
        if (is_array($v) && array_keys($v) !== range(0, count($v) - 1)) {
            fillScalarValues($v, $columnsUsed, array_merge($prefix, [$k]), $fixedValues);
        }
    }
}


/**
 * Gera o produto cartesiano de um array de listas.
 *
 * O produto cartesiano é a base para desdobrar listas aninhadas em múltiplas linhas,
 * criando uma nova linha para cada combinação de valores.
 *
 * @param array $listas Array de listas. Ex: `[['a','b'], [1,2]]`
 * @return array Um array de combinações. Ex: `[['a',1], ['a',2], ['b',1], ['b',2]]`
 */
function cartesianProduct(array $listas): array
{
    $result = [[]];
    foreach ($listas as $chave => $valores) {
        $tmp = [];
        foreach ($result as $res) {
            foreach ($valores as $val) {
                // Combina cada valor da lista atual com cada resultado anterior.
                $tmp[] = $res + [$chave => $val];
            }
        }
        $result = $tmp;
    }
    return $result;
}


/**
 * Descobre o índice de uma coluna (1-based) baseado em um array de chaves.
 *
 * @param array $columnsUsed Array das colunas mapeadas.
 * @param array $keys        Array de chaves (e.g., ['cliente', 'nome']).
 * @return int|null O índice da coluna (1-based) ou null se não for encontrada.
 */
function findColumnIndex($columnsUsed, $keys)
{
    foreach ($columnsUsed as $c) {
        if ($c['keys'] === $keys) {
            return $c['col'];
        }
    }
    return null;
}

/**
 * Escreve um valor em uma célula da planilha usando coordenadas numéricas.
 *
 * Esta função é um "wrapper" para a biblioteca PhpSpreadsheet, simplificando a
 * escrita de valores usando índices 1-based para coluna e linha.
 *
 * @param object $sheet     A aba da planilha.
 * @param int $colIndex     Índice da coluna (1-based).
 * @param int $rowIndex     Índice da linha (1-based).
 * @param mixed $value      O valor a ser escrito na célula.
 * @throws \InvalidArgumentException Se o índice da linha ou coluna for inválido.
 */
function setCellValueByColumnAndRow($sheet, $colIndex, $rowIndex, $value)
{
    if ($rowIndex < 1) {
        throw new \InvalidArgumentException("Row index inválido: $rowIndex");
    }
    if ($colIndex < 1) {
        throw new \InvalidArgumentException("Column index inválido: $colIndex");
    }
    // Converte o índice da coluna para a letra correspondente (e.g., 1 -> 'A').
    $cell = columnLetter($colIndex) . $rowIndex;

    $sheet->setCellValue($cell, $value);
}

/**
 * Converte um índice de coluna numérico (1-based) para a letra de coluna do Excel.
 *
 * Ex: 1 -> 'A', 26 -> 'Z', 27 -> 'AA'.
 *
 * @param int $colIndex O índice da coluna (1-based).
 * @return string A letra da coluna.
 */
function columnLetter($colIndex)
{
    $colName = '';
    while ($colIndex > 0) {
        $mod = ($colIndex - 1) % 26;
        $colName = chr(65 + $mod) . $colName;
        $colIndex = intdiv($colIndex - 1, 26);
    }
    return $colName;
}

/**
 * Converte qualquer valor para uma string segura para escrita.
 *
 * Garante que o valor seja uma string e que esteja em formato UTF-8, evitando
 * erros de codificação na planilha. Arrays e objetos são convertidos para JSON.
 *
 * @param mixed $v O valor a ser convertido.
 * @return string O valor convertido para string segura.
 */
function safeToString($v): string
{
    if (is_bool($v)) return $v ? '1' : '0';
    if (is_numeric($v)) return (string)$v;
    if (is_string($v)) {
        return mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'auto');
    }
    if (is_array($v) || is_object($v)) {
        // Converte arrays/objetos para uma string JSON.
        $json = json_encode($v, JSON_UNESCAPED_UNICODE);
        return mb_check_encoding($json, 'UTF-8') ? $json : mb_convert_encoding($json, 'UTF-8', 'auto');
    }
    return '';
}

/**
 * Robust nested getter for XML -> array structures.
 *
 * @param array $data The array to search.
 * @param array $keys Path keys (already exploded by '.' into an array).
 * @return mixed|null Scalar value if found, null otherwise.
 */
function getNestedValue($row, $keys)
{
    $current = $row;

    foreach ($keys as $key) {
        if (is_array($current)) {
            // Caso especial: arrays sequenciais
            if (array_keys($current) === range(0, count($current) - 1)) {
                $temp = [];
                foreach ($current as $item) {
                    $val = getNestedValue($item, [$key]); // tenta pegar do item
                    if ($val !== null) {
                        $temp[] = $val;
                    }
                }
                // Se coletamos valores, retornamos a lista
                if (!empty($temp)) {
                    $current = count($temp) === 1 ? $temp[0] : $temp;
                    continue;
                }
                return null;
            }

            // Caso normal: verificar se a chave existe
            if (array_key_exists($key, $current)) {
                $current = $current[$key];
                continue;
            }

            return null; // chave não existe
        }
        return null; // não é array
    }

    return $current;
}
