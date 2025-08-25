<?php

function processaArrayForExcel($modelo_colunas, $dados, $headers, $spreadsheet, $sheet, $modelo)
{
    // ---------- Mapeia colunas ----------
    $columnsUsed = [];
    foreach ($modelo_colunas as $i => $mc) {
        $descricao_coluna = $mc['descricao_coluna'];
        if (in_array($descricao_coluna, $headers, true)) {
            $keys = array_filter(explode('.', $descricao_coluna), 'strlen');
            $columnsUsed[] = [
                'header' => $descricao_coluna,
                'keys'   => array_values($keys),
                'col'    => $i + 1,
            ];
            setCellValueByColumnAndRow($sheet, $i + 1, 1, $descricao_coluna);
        }
    }

    if (!$columnsUsed) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: text/plain; charset=UTF-8');
        exit("Nenhuma coluna do modelo corresponde aos headers do arquivo.");
    }

    // ---------- Escreve dados ----------
    $rowIndex = 2;

    foreach ($dados as $row) {
        $colValues = [];
        $maxRows   = 1;

        // 1) Extrai valores
        foreach ($columnsUsed as $c) {
            $vals = array_values(getNestedValues($row, $c['keys']) ?: [""]);
            $colValues[$c['col']] = $vals;
            $maxRows = max($maxRows, count($vals));
        }

        // 2) Escreve linhas expandidas
        for ($i = 0; $i < $maxRows; $i++) {
            foreach ($columnsUsed as $c) {
                $valor = $colValues[$c['col']][$i] ?? "";
                if (is_array($valor)) {
                    $valor = $valor[end($c['keys'])] ?? "";
                }
                setCellValueByColumnAndRow($sheet, $c['col'], $rowIndex + $i, safeToString($valor));
            }
        }

        $rowIndex += $maxRows;
    }

    // ---------- Salvar arquivo ----------
    $destinoDir = __DIR__ . "/../../assets/convertidos/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/";
    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true)) {
        throw new \RuntimeException("Não foi possível criar pasta de destino: {$destinoDir}");
    }

    $caminhoFinal = $destinoDir . "arquivos_" . date("Ymd_His") . ".xlsx";

    try {
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($caminhoFinal);
    } catch (\Throwable $e) {
        @file_put_contents($destinoDir . 'save_error_' . date('Ymd_His') . '.log', $e->__toString());
    }

    if (!filesize($caminhoFinal)) {
        @file_put_contents($destinoDir . 'save_error_zero_' . date('Ymd_His') . '.log', "Arquivo {$caminhoFinal} não existe ou está vazio");
        throw new \RuntimeException("Falha ao salvar arquivo: {$caminhoFinal}");
    }
}

/** Escreve valor na planilha por coluna/linha (1-based). */
function setCellValueByColumnAndRow($sheet, $colIndex, $rowIndex, $value)
{
    $sheet->setCellValue(columnLetter($colIndex) . $rowIndex, $value);
}

/** Converte índice 1-based para letra de coluna (A,B,...,AA). */
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

/** Converte qualquer valor em string segura UTF-8. */
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

/** Extrai valores aninhados seguindo lista de chaves (expande listas corretamente). */
function getNestedValues($data, $keys)
{
    $key = array_shift($keys);
    if (!isset($data[$key])) return [];

    $value = $data[$key];

    if (empty($keys)) {
        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                return array_map(fn($v) => is_array($v) ? $v : (string)$v, $value);
            }
            return [$value];
        }
        return [(string)$value];
    }

    if (is_array($value)) {
        if (array_keys($value) === range(0, count($value) - 1)) {
            $results = [];
            foreach ($value as $v) $results = array_merge($results, getNestedValues($v, $keys));
            return $results;
        }
        return getNestedValues($value, $keys);
    }

    return [];
}
