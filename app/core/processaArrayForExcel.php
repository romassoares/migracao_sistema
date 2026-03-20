<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;

ini_set('memory_limit', '2G');

$layout_colunas_depara = [];
$spreadsheet = new stdClass();

// $spreadsheetCertos = new stdClass();
$sheet = new stdClass();
// $sheetCertos = new stdClass();

function setHeaderAndRetornColumns($modelo_colunas, $dados, $headers, $modelo,  $layout_colunas)
{
    global $spreadsheet, $sheet, $sheetCertos, $layout_colunas_depara;
    // writeInFileLog(json_encode($modelo_colunas));
    // Contador de colunas
    $columnsUsed = []; // Colunas que serão efetivamente usadas
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
        // writeInFileLog('key: ' . $key . ' coluna: ' . $coluna);
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

// 
function writeRowRecursive($data, $columnsUsed, $rowIndex, $ifExistErro, $prefix = [], $fixedValues = [], &$lastRowValues = [])
{
    if (is_object($data)) {
        $data = json_decode(json_encode($data), true);
    }

    // $total_colunas_por_linha = count($columnsUsed);
    $total_colunas_por_linha = count(
        array_unique(array_column($columnsUsed, 'col'))
    );
    // writeInFileLog($total_colunas_por_linha);    
    foreach ($columnsUsed as $c) {
        if (
            count($c['keys']) === count($prefix) + 1 &&
            array_slice($c['keys'], 0, count($prefix)) === $prefix
        ) {
            $key = end($c['keys']);
            $valor = $data[$key] ?? "";
            if (!is_array($valor)) {
                // writeInFileLog('fixedValues');
                $fixedValues[$c['col']] = $valor;
            }
        }
    }

    // Separa listas e objetos aninhados
    $listas = [];
    $objetos = [];
    foreach ($data as $k => $v) {
        if (is_array($v) && array_keys($v) === range(0, count($v) - 1)) { // Lista (array indexado)
            // writeInFileLog('separando listas');
            $listas[$k] = $v;
        } elseif (is_array($v)) { // Objeto (array associativo)
            // writeInFileLog('separando Objeto');
            $objetos[$k] = $v;
        }
    }

    // Processa listas com produto cartesiano
    if ($listas) {
        // writeInFileLog("lista"); 
        // writeInFileLog('listas: ' . print_r($fixedValues, true));
        // debugTime('inicio  processa listas');
        $combos = cartesianProduct($listas);
        foreach ($combos as $combo) {
            $rowVals = $fixedValues;
            $filhoEscreveu = false;

            foreach ($combo as $chaveLista => $valorLista) {
                if (is_scalar($valorLista) || $valorLista === null) {
                    $col = findColumnIndex($columnsUsed, array_merge($prefix, [$chaveLista]));
                    if ($col) {
                        $rowVals[$col] = $valorLista;
                        // $rowVals[$col] = safeToString($valorLista);
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
        // debugTime('FIm  processa listas  ');
        return $rowIndex;
    }

    // Processa objetos associativos
    if ($objetos) {
        // writeInFileLog("objetos"); 
        // writeInFileLog('objetos: ' . print_r($fixedValues, true));
        // debugTime('inicio  processa objetos');
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
            // debugTime('fim  processa objetos maior que antes');
            return $rowIndex;
        }

        foreach ($objetos as $chObj => $objVal) {
            // Preenche valores escalares do objeto atual
            fillScalarValues($objVal, $columnsUsed, array_merge($prefix, [$chObj]), $fixedValues);
        }
        if ($fixedValues !== $lastRowValues  && count($fixedValues) == $total_colunas_por_linha) {
            foreach ($fixedValues as $col => $valor) {
                setCellValueByColumnAndRow($col, $rowIndex, $valor, $ifExistErro);
            }
            $lastRowValues = $fixedValues;
            $rowIndex++;
        }
        // debugTime('fim  processa objetos igual ou menor que antes');
        return $rowIndex;
    }

    // Caso final: linha simples, sem listas nem objetos
    // if ($fixedValues !== $lastRowValues && count($fixedValues) == $total_colunas_por_linha) {
    if ($fixedValues !== $lastRowValues) {

        // writeInFileLog('Caso final: ' . print_r($fixedValues, true));
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
/**
 * Preenche valores escalares de $data em $fixedValues com base nas colunas informadas.
 *
 * Esta função percorre as colunas definidas em $columnsUsed e, para cada coluna,
 * tenta localizar um valor correspondente em $data. Ao encontrar um valor escalar
 * (string, int, float, bool ou null), atribui esse valor em $fixedValues usando a
 * chave da coluna. É possível especificar um $prefixo opcional que será concatenado
 * antes do nome da coluna ao procurar a chave dentro de $data.
 *
 * Observações sobre comportamento:
 * - Apenas valores escalares são copiados; valores não escalares (arrays, objetos, recursos)
 *   são ignorados.
 * - A função não retorna valor diretamente; ela modifica $fixedValues por referência.
 * - Se a chave correspondente a uma coluna não existir em $data, $fixedValues permanece
 *   inalterado para aquela coluna.
 *
 * @param mixed      $data        Fonte de dados (array associativo ou objeto) onde as chaves/valores serão buscados.
 * @param array      $columnsUsed Lista de colunas (nomes ou mapeamentos) a serem consideradas na extração.
 * @param array      &$fixedValues Array passado por referência que receberá os pares coluna => valor extraídos.
 *
 * @return void
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
                // $fixedValues[$c['col']] = safeToString($data[$key]);
                $fixedValues[$c['col']] = $data[$key];
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
    global $layout_colunas_depara, $spreadsheet, $sheet, $ifExistErro;

    // writeInFileLog($value);

    if ($rowIndex < 1)
        throw new \InvalidArgumentException("Row index inválido: $rowIndex");

    if ($colIndex < 1)
        throw new \InvalidArgumentException("Column index inválido: $colIndex");

    $cell = columnLetter($colIndex) . $rowIndex;
    // writeInFileLog($value); 
    // writeInFileLog($cell); 
    $valueCriticado = "";

    $valueTodos = "";

    if ($rowIndex == 1) {
        $valueCriticado = $value;
        $valueTodos = $value;
    } else {

        foreach ($layout_colunas_depara as $depara) {
            if (intval($depara['posicao']) == $colIndex) {



                if ($depara['tipo'] == 'numerico') {
                    if (intval($depara['obrigatorio']) == 1 && empty($value)) {
                        $valueCriticado = $value . ' Critica: Campo é obrigatório';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    }
                    if ($value == '') {
                        $value = 0;
                    }
                    $value = preg_replace('/[^\d,-]/', '', $value);
                    $value = str_replace(',', '.', $value);
                    if (!is_numeric($value)) {
                        $valueCriticado = $value . ' Critica: Não é numérico';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    } else {
                        // $valueCorreto = $value;
                        $valueTodos = $value;
                        break;
                    }
                }

                if ($depara['tipo'] == 'data' && !empty($value)) {
                    $date = null;
                    if ($value !== '0000-00-00') {
                        $date = parseDateFlexible($value);
                    }
                    if ($date) { // Data válida, converte para padrão
                        if ($value !== '0000-00-00') {
                            $valueTodos = $date->format('Y-m-d');
                        } else {
                            $valueTodos = $value;
                        }
                        // $valueCorreto = $value;
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
                        // $valueCorreto = $value;
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
                        // $valueCorreto = $value;
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
                        // $valueCorreto = $value;
                        $valueTodos = $value;
                        break;
                    }
                }





                if (empty($depara['tipo']) || (is_string($depara['tipo']) && strtolower($depara['tipo']) == 'flag') && count($depara['depara']) > 0) {
                    // var_dump($depara, $value);
                    // die;
                    $mapa = [];
                    foreach ($depara['depara'] as $m) {

                        if (strtolower($value) == strtolower($m['conteudo_de'])) {
                            $mapa[strtolower(trim($m['conteudo_de']))] = $m['Conteudo_para_livre'];
                            break;
                        }
                    }

                    $key = strtolower(trim($value));
                    if (isset($mapa[$key])) {
                        // $valueCorreto = $mapa[$key];
                        $valueTodos = $mapa[$key];
                        break;
                    } else {
                        $valueCriticado = $value . ' Critica: Valor não mapeado';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    }
                }

                if (empty($depara['tipo']) || strtolower($depara['tipo']) == 'livre') {
                    if (intval($depara['obrigatorio']) == 1 && empty($value)) {
                        $valueCriticado = $value . ' Critica: Campo é obrigatório';
                        $valueTodos = $value;
                        $ifExistErro = true;
                        break;
                    }
                    $value = strip_tags($value);

                    if (strval($value) == "0") {
                        $value = "";
                    }
                    // $value = RemoveStrangeCharacter($value);
                    // $valueCorreto = $value;
                    $valueTodos = $value;
                    break;
                }
            }
        }
    }

    UpdateArquivoCriticadoLog($rowIndex, $cell . " - " . $valueCriticado);

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

function getNestedValue($row, $keys)
{
    $current = $row;

    foreach ($keys as $key) {
        if (!is_array($current)) {
            return null;
        }

        // Detecta array sequencial
        $isSequential = isset($current[0]) && array_keys($current) === range(0, count($current) - 1);

        if ($isSequential) {
            $temp = [];
            foreach ($current as $item) {
                if (is_array($item) && array_key_exists($key, $item)) { // Item é array associativo
                    $val = $item[$key];
                } else { // Item não é array ou chave não existe
                    $val = getNestedValue($item, [$key]);
                }

                if ($val !== null) { // Acumula valores não nulos
                    $temp[] = $val;
                }
            }

            if ($temp) { // Mantém formato original (único valor → escalar)
                $current = count($temp) === 1 ? $temp[0] : $temp;
                continue;
            }
            return null;
        }

        // Array associativo
        if (array_key_exists($key, $current)) {
            $current = $current[$key];
            continue;
        }

        return null;
    }

    return $current;
}


function UpdateArquivoCriticadoLog($linha, $txt)
{
    global $arquivoCriticado;

    // Adiciona o texto no final do arquivo
    file_put_contents($arquivoCriticado, $txt . PHP_EOL, FILE_APPEND);
}
