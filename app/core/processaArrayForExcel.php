<?php
require_once(__DIR__ . '/../Controller/ArquivoController.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;

$layout_colunas_depara = [];
$spreadsheet = new stdClass();
$spreadsheetCriticas = new stdClass();
$spreadsheetCertos = new stdClass();
$sheet = new stdClass();
$sheetCriticas = new stdClass();
$sheetCertos = new stdClass();

// $debug = false;


/**
 * Função principal que orquestra o processo de exportação dos dados para Excel.
 * Ela:
 *  - Define as colunas com base no modelo.
 *  - Filtra os dados para evitar duplicações.
 *  - Escreve os dados na planilha.
 *  - Salva o arquivo em disco.
 */
function setHeaderAndRetornColumns($modelo_colunas, $dados, $headers, $modelo,  $layout_colunas, $ifExistErro)
{
    global $spreadsheet, $sheet, $sheetCriticas, $sheetCertos, $layout_colunas_depara;

    // Contador de colunas
    $columnsUsed = []; // Colunas que serão efetivamente usadas
    // dd($modelo_colunas, $layout_colunas);
    foreach ($modelo_colunas as $mc) {
        $descricao_coluna = $mc['descricao_coluna'];

        // Só usa a coluna se ela existir nos headers de origem
        if (in_array($descricao_coluna, $headers, true)) {
            // Divide nomes hierárquicos (ex: cliente/nome -> ['cliente','nome'])
            $keys = array_filter(explode('/', $descricao_coluna), 'strlen');
            $columnsUsed[] = [
                'header' => $descricao_coluna,
                'keys'   => array_values($keys), // Caminho de chaves para acessar no array de dados
                'col'    => $mc['posicao'], // Índice numérico da coluna
            ];
        }
    }

    foreach ($layout_colunas as $key => $coluna) {
        setCellValueByColumnAndRow($key, 1, $coluna, false);
    }
    // Caso nenhuma coluna seja compatível, encerra com erro
    if (!$columnsUsed) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: text/plain; charset=UTF-8');
        exit("Nenhuma coluna do modelo corresponde aos headers do arquivo.");
    }

    return $columnsUsed;
}

/**
 * Escreve dados recursivamente, lidando com arrays aninhados e listas.
 * - "Achata" estruturas complexas.
 * - Usa produto cartesiano para listas.
 * - Evita linhas duplicadas.
 */
function writeRowRecursive($data, $columnsUsed, $rowIndex, $ifExistErro, $prefix = [], $fixedValues = [], &$lastRowValues = [])
{
    // Preenche valores escalares do nível atual
    $total_colunas_por_linha = count($columnsUsed);
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

    foreach ($data as $k => $v) {
        if (is_array($v) && (array_keys($v) === range(0, count($v) - 1))) {
            $listas[$k] = $v;
        } elseif (is_array($v)) {
            $objetos[$k] = $v;
        }
    }

    // Processa listas com produto cartesiano
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
                } else { // Valor é objeto aninhado, processa recursivamente
                    $antes = $rowIndex;
                    $rowResponse = writeRowRecursive(
                        $valorLista,
                        $columnsUsed,
                        $rowIndex,
                        $ifExistErro,
                        array_merge($prefix, [$chaveLista]),
                        $rowVals,
                        $lastRowValues
                    );
                    $rowIndex = $rowResponse;
                    if ($rowIndex > $antes) $filhoEscreveu = true;
                }
            }

            if ($filhoEscreveu) continue;

            // Evita duplicação
            if ($rowVals !== $lastRowValues && count($rowVals) == $total_colunas_por_linha) {
                foreach ($rowVals as $col => $valor) {
                    setCellValueByColumnAndRow($col, $rowIndex, $valor, $ifExistErro);
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
            $rowResponse = writeRowRecursive(
                $objVal,
                $columnsUsed,
                $rowIndex,
                $ifExistErro,
                array_merge($prefix, [$chObj]),
                $fixedValues,
                $lastRowValues
            );
            $rowIndex = $rowResponse;
        }
        if ($rowIndex > $antes) {
            return $rowIndex;
        }
        $rowVals = $fixedValues;
        foreach ($objetos as $chObj => $objVal) {
            fillScalarValues($objVal, $columnsUsed, array_merge($prefix, [$chObj]), $rowVals);
        }
        if ($rowVals !== $lastRowValues  && count($rowVals) == $total_colunas_por_linha) {
            foreach ($rowVals as $col => $valor) {
                setCellValueByColumnAndRow($col, $rowIndex, $valor, $ifExistErro);
            }
            $lastRowValues = $rowVals;
            $rowIndex++;
        }
        return $rowIndex;
    }


    // Caso final: linha simples, sem listas nem objetos
    if ($fixedValues !== $lastRowValues && count($fixedValues) == $total_colunas_por_linha) {
        foreach ($fixedValues as $col => $valor) {
            setCellValueByColumnAndRow($col, $rowIndex, $valor, $ifExistErro);
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
function setCellValueByColumnAndRow($colIndex, $rowIndex, $value, $ifExistErro)
{
    global $layout_colunas_depara, $spreadsheetCriticas, $spreadsheetCertos, $spreadsheet, $sheetCriticas, $sheetCertos, $sheet, $ifExistErro;

    if ($rowIndex < 1) {
        throw new \InvalidArgumentException("Row index inválido: $rowIndex");
    }
    if ($colIndex < 1) {
        throw new \InvalidArgumentException("Column index inválido: $colIndex");
    }
    $cell = columnLetter($colIndex) . $rowIndex;

    $valueCriticado = "";
    $valueCorreto = "";
    $valueTodos = "";

    // writeInFileLog($colIndex . '  -- ' . $cell . ' -- ' . $value);
    if ($rowIndex == 1) {
        $valueCorreto = $value;
        $valueCriticado = $value;
        $valueTodos = $value;
    } else {
        foreach ($layout_colunas_depara as $depara) {
            // writeInFileLog($depara['tipo'] . '  --  ' . $depara['posicao'] .  '  --  ' . $colIndex);
            if (intval($depara['posicao']) == $colIndex) {

                if (intval($depara['obrigatorio']) == 1 && empty($value)) {
                    $valueCriticado = $value . ' Critica: Campo é obrigatório';
                    $valueTodos = $value;
                    $ifExistErro = true;
                    break;
                }

                if (strtolower($depara['tipo']) == 'livre' || empty($depara['tipo'])) {
                    $value = strip_tags($value);
                    $value = RemoveStrangeCharacter($value);
                    $valueCorreto = $value;
                    $valueTodos = $value;
                    break;
                }

                if ($depara['tipo'] == 'numerico') {
                    $value = preg_replace('/[^\d,-]/', '', $value);
                    $value = str_replace(',', '.', $value);
                    if (!is_numeric($value)) {
                        $valueCriticado = $value . ' Critica: Não é numérico';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    } else {
                        $valueCorreto = $value;
                        $valueTodos = $value;
                        break;
                    }
                }

                if ($depara['tipo'] == 'data') {
                    $formatos = [
                        'd/m/Y'
                    ];
                    $value = preg_replace('/\s+/', ' ', trim($value));

                    $date = false;
                    foreach ($formatos as $formato) {
                        $d = DateTime::createFromFormat($formato, $value);
                        if ($d && $d->format($formato) === $value) {
                            $date = $d;
                            break;
                        }
                    }

                    if ($date) {
                        $valueTodos = $date->format('Y-m-d');
                        $valueCorreto = $value;
                        break;
                    } else {
                        $valueCriticado = $value . ' Critica: Formato data inválido';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    }
                }

                if ($depara['tipo'] == 'email') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $valueCriticado = $value . ' Critica: Email inválido';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    } else {
                        $valueCorreto = $value;
                        $valueTodos = $value;
                        break;
                    }
                }

                if ($depara['tipo'] == 'telefone') {
                    if (!preg_match('/^\+?[0-9\s\-\(\)]+$/', $value)) {
                        $valueCriticado = $value . ' Critica: Telefone inválido';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    } else {
                        $valueCorreto = $value;
                        $valueTodos = $value;
                        break;
                    }
                }

                if ($depara['tipo'] == 'sim_nao') {
                    $valores_aceitos = ['sim', 'não', 'nao', 's', 'n', '1', '0', 'true', 'false', 'verdadeiro', 'falso'];
                    if (!in_array(strtolower(trim($value)), $valores_aceitos)) {
                        $valueCriticado = $value . ' Critica: Valor inválido (Sim/Não)';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    } else {
                        $valueCorreto = $value;
                        $valueTodos = $value;
                        break;
                    }
                }

                if (strtolower($depara['tipo']) == 'flag' && count($depara['depara']) > 0) {
                    $mapa = [];

                    foreach ($depara['depara'] as $m) {
                        if ($value == $m['conteudo_de'] && $m['substituir'] == '1') {
                            $mapa[strtolower(trim($m['conteudo_de']))] = $m['Conteudo_para_livre'];
                            break;
                        }
                    }

                    $key = strtolower(trim($value));
                    if (isset($mapa[$key])) {
                        $valueCorreto = $mapa[$key];
                        $valueTodos = $mapa[$key];
                        break;
                    } else {
                        $valueCriticado = $value . ' Critica: Valor não mapeado';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    }
                }
            }
        }
    }



    $sheetCriticas->setCellValue($cell, $valueCriticado);
    $sheetCertos->setCellValue($cell, $valueCorreto);
    $sheet->setCellValue($cell, $valueTodos);

    return;
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
