<?php

/**
 * Função principal que orquestra o processo de exportação dos dados para Excel.
 * Ela:
 *  - Define as colunas com base no modelo.
 *  - Filtra os dados para evitar duplicações.
 *  - Escreve os dados na planilha.
 *  - Salva o arquivo em disco.
 */
function processaArrayForExcel($modelo_colunas, $dados, $headers, $spreadsheet, $sheet, $modelo)
{
    // ---------- Mapeia colunas do modelo para as colunas disponíveis nos headers ----------
    $i = 1; // Contador de colunas
    $columnsUsed = []; // Colunas que serão efetivamente usadas
    foreach ($modelo_colunas as $mc) {
        $descricao_coluna = $mc['descricao_coluna'];
        // Só usa a coluna se ela existir nos headers de origem
        if (in_array($descricao_coluna, $headers, true)) {
            // Divide nomes hierárquicos (ex: cliente.nome -> ['cliente','nome'])
            $keys = array_filter(explode('.', $descricao_coluna), 'strlen');
            $columnsUsed[] = [
                'header' => $descricao_coluna,
                'keys'   => array_values($keys), // Caminho de chaves para acessar no array de dados
                'col'    => $i, // Índice numérico da coluna
            ];
            // Escreve o cabeçalho na planilha
            setCellValueByColumnAndRow($sheet, $i, 1, $descricao_coluna);
            $i++;
        }
    }

    // Caso nenhuma coluna seja compatível, encerra com erro
    if (!$columnsUsed) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: text/plain; charset=UTF-8');
        exit("Nenhuma coluna do modelo corresponde aos headers do arquivo.");
    }

    // Pega a primeira coluna como chave para identificar duplicatas
    $colunaChave1 = $columnsUsed[0]['keys'] ?? null;

    // ---------- Otimização: Processa grupos únicos para reduzir memória ----------
    $batchSize = 100; // Tamanho do lote de limpeza de memória
    $rowIndex = 2; // Começa a escrever na linha 2 (linha 1 é cabeçalho)
    $processedCount = 0;
    $gruposUnicos = [];

    // Agrupa dados únicos pela primeira coluna
    foreach ($dados as $row) {
        $valor1 = $colunaChave1 ? getNestedValue($row, $colunaChave1) : '';
        $chaveGrupo = is_array($valor1) ? implode(',', $valor1) : (string)$valor1;
        if (!isset($gruposUnicos[$chaveGrupo])) {
            $gruposUnicos[$chaveGrupo] = $row;
        }
        $processedCount++;
        if ($processedCount % 1000 === 0) {
            gc_collect_cycles(); // Limpa memória a cada 1000 registros
        }
    }

    // ---------- Escreve os grupos únicos na planilha ----------
    $processedCount = 0;
    foreach ($gruposUnicos as $row) {
        $rowIndex = writeRowRecursive($sheet, $row, $columnsUsed, $rowIndex);
        $processedCount++;
        if ($processedCount % $batchSize === 0) {
            $spreadsheet->garbageCollect();
            gc_collect_cycles();
        }
    }

    // ---------- Salva arquivo ----------
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
 * Escreve dados recursivamente, lidando com arrays aninhados e listas.
 * - "Achata" estruturas complexas.
 * - Usa produto cartesiano para listas.
 * - Evita linhas duplicadas.
 */
function writeRowRecursive($sheet, $data, $columnsUsed, $rowIndex, $prefix = [], $fixedValues = [], &$lastRowValues = [])
{
    // Preenche valores escalares do nível atual
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

    // Separa listas e objetos aninhados
    $listas = [];
    $objetos = [];
    // $keysQuebram = ['image', 'foto', 'feature']; // personalize

    $debug = false;


    foreach ($data as $k => $v) {

        if ($k == 'Condominios') {
            $debug = true;
            // var_dump(array_keys($v));
            // die;
        }

        if (is_array($v) && (array_keys($v) === range(0, count($v) - 1))) {
            $listas[$k] = $v;
        } elseif (is_array($v)) {
            // var_dump($v);
            // die;
            $objetos[$k] = $v;
        }
    }

    // if ($debug) {
    //     var_dump($listas, $objetos);
    //     die;
    // }

    // Processa listas com produto cartesiano
    if ($listas) {
        $combos = cartesianProduct($listas);
        foreach ($combos as $combo) {

            $rowVals = $fixedValues;
            $filhoEscreveu = false;

            foreach ($combo as $chaveLista => $valorLista) {
                // if (strtolower($chaveLista) == 'foto') {
                //     echo 'listas';
                //     var_dump($chaveLista, $valorLista, $listas);
                //     die;
                // }
                if (is_scalar($valorLista) || $valorLista === null) {

                    $col = findColumnIndex($columnsUsed, array_merge($prefix, [$chaveLista]));
                    if ($col) {
                        $rowVals[$col] = safeToString($valorLista);
                    }
                } else {

                    // Valor é objeto aninhado, processa recursivamente
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

            if ($filhoEscreveu) continue;

            // Evita duplicação
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

    // Processa objetos associativos
    if ($objetos) {
        $antes = $rowIndex;

        foreach ($objetos as $chObj => $objVal) {

            // if (strtolower($chObj) == 'foto') {
            //     echo 'objetos';
            //     var_dump($chObj, $objVal, $objetos);
            //     die;
            // }

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
        if ($rowVals !== $lastRowValues) {
            foreach ($rowVals as $col => $valor) {
                setCellValueByColumnAndRow($sheet, $col, $rowIndex, $valor);
            }
            $lastRowValues = $rowVals;
            $rowIndex++;
        }
        return $rowIndex;
    }

    // Caso final: linha simples, sem listas nem objetos
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
 * Preenche valores escalares de toda a subárvore (sem listas) em $fixedValues.
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

    // Recursão para objetos aninhados
    foreach ($data as $k => $v) {
        if (is_array($v) && array_keys($v) !== range(0, count($v) - 1)) {
            fillScalarValues($v, $columnsUsed, array_merge($prefix, [$k]), $fixedValues);
        }
    }
}

/**
 * Retorna todas as combinações possíveis (produto cartesiano) das listas.
 */
function cartesianProduct(array $listas): array
{
    $result = [[]];
    foreach ($listas as $chave => $valores) {
        $tmp = [];
        foreach ($result as $res) {
            foreach ($valores as $val) {
                $tmp[] = $res + [$chave => $val];
            }
        }
        $result = $tmp;
    }
    return $result;
}

/**
 * Busca o índice de coluna no mapeamento baseado nas chaves.
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
 * Wrapper para setar valores em células com índice numérico (coluna/linha).
 */
function setCellValueByColumnAndRow($sheet, $colIndex, $rowIndex, $value)
{
    if ($rowIndex < 1) {
        throw new \InvalidArgumentException("Row index inválido: $rowIndex");
    }
    if ($colIndex < 1) {
        throw new \InvalidArgumentException("Column index inválido: $colIndex");
    }
    $cell = columnLetter($colIndex) . $rowIndex;
    $sheet->setCellValue($cell, $value);
}

/**
 * Converte índice numérico de coluna para letra do Excel (ex: 1 -> A, 27 -> AA).
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
 * Converte qualquer valor em string segura (UTF-8).
 */
function safeToString($v): string
{
    if (is_bool($v)) return $v ? '1' : '0';
    if (is_numeric($v)) return (string)$v;
    if (is_string($v)) {
        return mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'auto');
    }
    if (is_array($v) || is_object($v)) {
        $json = json_encode($v, JSON_UNESCAPED_UNICODE);
        return mb_check_encoding($json, 'UTF-8') ? $json : mb_convert_encoding($json, 'UTF-8', 'auto');
    }
    return '';
}

/**
 * Busca valor aninhado em arrays usando caminho de chaves.
 */
function getNestedValue($row, $keys)
{
    $current = $row;
    foreach ($keys as $key) {
        if (is_array($current)) {
            // Caso de array sequencial (lista)
            if (array_keys($current) === range(0, count($current) - 1)) {
                $temp = [];
                foreach ($current as $item) {
                    $val = getNestedValue($item, [$key]);
                    if ($val !== null) {
                        $temp[] = $val;
                    }
                }
                if (!empty($temp)) {
                    $current = count($temp) === 1 ? $temp[0] : $temp;
                    continue;
                }
                return null;
            }
            // Caso de array associativo
            if (array_key_exists($key, $current)) {
                $current = $current[$key];
                continue;
            }
            return null;
        }
        return null;
    }
    return $current;
}
