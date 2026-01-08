<?php
include_once(__DIR__ . '/../core/includes.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;

function index()
{
    $counts = getCounts($_SESSION['company']['codigo_cliente']);
    // $counts = [];
    return ['view' => 'vista/index', 'data' => ['counts' => $counts], 'function' => ''];
}

function store()
{
    $codigo_infoideias = $_SESSION['company']['codigo_cliente'];

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return return_api(400, 'Erro ao carregar arquivo SQL', []);
    }

    $tmpPath = $_FILES['file']['tmp_name'];
    $sql_script = file_get_contents($tmpPath);

    if ($sql_script === false || trim($sql_script) === '') {
        return return_api(400, 'Arquivo SQL vazio ou inválido', []);
    }

    $sql_script = normalize_and_fix_sql($sql_script, $codigo_infoideias);;

    $ok = multi_query_db($sql_script['sql'], 'migracao');

    if ($ok) {
        $counts = getCounts($codigo_infoideias);

        return return_api(200, 'SQL executado com sucesso', ['counts' => $counts]);
    } else {
        return return_api(500, 'Erro ao executar SQL', []);
    }
}

function getCounts($codigo_infoideias)
{
    $counts = [];
    if (verifica_tabela_exist('cadcli')) {
        $counts['clientes_proprietario'] = count(clientes_proprietario($codigo_infoideias));
    }
    if (verifica_tabela_exist('cdcliag') && verifica_tabela_exist('cadcli') && verifica_tabela_exist('cademp')) {
        $counts['corretores_clientes'] = count(corretores_clientes($codigo_infoideias));
    }
    if (verifica_tabela_exist('cadimo') && verifica_tabela_exist('cadcat')) {
        $counts['imoveis'] = count(imoveis($codigo_infoideias));
    }
    if (verifica_tabela_exist('cdimag') && verifica_tabela_exist('cadcat') && verifica_tabela_exist('cadcli')) {
        $counts['imovel_proprietario'] = count(imovel_proprietario($codigo_infoideias));
    }
    if (verifica_tabela_exist('cadimo') && verifica_tabela_exist('cdimag') && verifica_tabela_exist('cademp')) {
        $counts['opcionistas_imovel'] = count(opcionistas_imovel($codigo_infoideias));
    }
    if (verifica_tabela_exist('cdimim')) {
        $counts['fotos_imovel'] = count(fotos_imovel($codigo_infoideias));
    }
    // if (verifica_tabela_exist('cademp')) {
    //     $counts['usuarios_sistema'] = count(usuarios_sistema($codigo_infoideias));
    // }
    // if (verifica_tabela_exist('cadcat')) {
    //     $counts['cadcat'] = count(metodo_all("SELECT * FROM cadcat where codigo_infoideias = $codigo_infoideias", 'migracao'));
    // }
    if (verifica_tabela_exist('cademp')) {
        $counts['usuarios_sistema'] = count(usuarios_sistema($codigo_infoideias));
    }

    return $counts;
}

function clientes_proprietario($codigo_infoideias)
{
    // PLANILHA DE CLIENTES E PROPRIETARIOS
    $sql = "SELECT CODIGO_C, NOME, STATUS, CLIENTE, PROPRIETARIO, ORIGEM, CONCAT('dataed_',DATA) AS data_cadastro, CONCAT('dataed_',DATA_ATZ) AS ult_atualizacao, EMAIL_R, EMAIL_C, FONE_PRINCIPAL, '' AS comp_tel_1, '' AS tipo_tel_1, FONE_RAMAL, '' AS comp_tel_2, '' AS tipo_tel_2, FONE_R, '' AS comp_tel_3, '' AS tipo_tel_3, FONE_C, '' AS comp_tel_4, '' AS tipo_tel_4, OBS AS OBSERVACOES , '' AS forma_contato, '' AS tel_1_ddd, '' AS tel_2_ddd, '' AS tel_3_ddd, '' AS tel_4_ddd
    FROM cadcli
    where codigo_infoideias = $codigo_infoideias";

    return metodo_all($sql, 'migracao');
}


function corretores_clientes($codigo_infoideias)
{
    // PLANILHA DE CORRETORES VINCULADOS AOS CLIENTES 
    $sql = "SELECT CADCLI.CODIGO_C, CADCLI.NOME AS CLIENTE, CADEMP.CODIGO_D, CADEMP.NOME AS CORRRETOR
            FROM CADCLI
            LEFT JOIN CDCLIAG ON CDCLIAG.CODIGO_C = CADCLI.CODIGO_C
            INNER JOIN CADEMP ON CADEMP.CODIGO_D = CDCLIAG.CODIGO_D
            WHERE CADCLI.CLIENTE='Sim' AND CADCLI.codigo_infoideias = $codigo_infoideias";
    // dd($sql);
    return metodo_all($sql, 'migracao');
}


function imoveis($codigo_infoideias)
{
    // PLANILHA DE IMÓVEIS  
    $sql = "SELECT  
	i.CODIGO AS id_imovel,
	i.CODIGO AS codigo_imovel,
	ENDERECO_TIPO AS tipo_ender,
	ENDERECO AS nome_rua,
	NUM_ENDERECO,
	BLOCO,
	LOTE,
	QUADRA,
	LTRIM(REPLACE(ENDERECO_MIGRADO, CONCAT(ENDERECO_TIPO, ' ',ENDERECO,' ',NUM_ENDERECO) ,'')) AS complemento,
	BAIRRO,
	BAIRRO_COMERCIAL,
	CIDADE,
	UF,
	CEP,
	GMAPS_LAT AS latitude,
	GMAPS_LNG AS longitude,
	EMPREENDIMENTO AS nome_condominio,
    i.STATUS,
    Lancamento AS venda,
    IF (ISNULL(VLR_VENDA) , 0.00, VLR_VENDA) AS vlr_venda,
    IF ( VLR_ALUGUEL > 0 , 'Anual', '') AS locacao ,  
    IF (ISNULL(VLR_ALUGUEL) , 0.00, VLR_ALUGUEL) AS vlr_locacao, 
    -- INSCRICAO_IPTU,
	CONCAT('dataed_',DATA) AS data_cadastro,
	CONCAT('dataed_',DATA_ATUALIZACAO) AS DATA_ULT_ATUALIZ,
    '' AS publica_site,
	IF (ISNULL(VLR_CONDOMINIO) , 0.00, VLR_CONDOMINIO) AS VLR_CONDOMINIO,
	IF (ISNULL(VLR_IPTU) , 0.00, VLR_IPTU) AS VLR_IPTU,
	TITULO_WEB,
	DESCRICAO_PARA_WEB,
	OBS_VENDA,
	-- TOUR_VIRTUAL, 
	LINK_VIDEO,
	i.DORMITORIO,
	i.SUITE,
	VAGAS,
	VAGAS_COBERTAS,
	VAGAS_DESCOBERTAS,
	BANHEIRO_SOCIAL,
	VARANDAS,
	SALAS,
	ELEVADORES,
	ANO_CONSTRUCAO,
	-- AREA_LOCAVEL,
	AREA_TOTAL,
	AREA_TERRENO,
    ANDAR_APTO AS andar,
	CASE WHEN TEM_PLACA = 'SIM' THEN 'S' ELSE 'N' END AS TEM_PLACA,
	CASE WHEN PISCINA_COLETIVA = 'SIM' THEN 'S' ELSE 'N' END AS PISCINA_COLETIVA,
	CASE WHEN SAUNA = 'SIM' THEN 'S' ELSE 'N' END AS SAUNA,
	CASE WHEN CHURRASQUEIRA_COLETIVA = 'SIM' THEN 'S' ELSE 'N' END AS CHURRASQUEIRA_COLETIVA,
	CASE WHEN SALA_JOGOS = 'SIM' THEN 'S' ELSE 'N' END AS SALA_JOGOS,
	CASE WHEN SALA_GINASTICA = 'SIM' THEN 'S' ELSE 'N' END AS SALA_GINASTICA,
	CASE WHEN AR_CONDICIONADO = 'SIM' THEN 'S' ELSE 'N' END AS AR_CONDICIONADO,
	CASE WHEN QUADRA_ESPORTES = 'SIM' THEN 'S' ELSE 'N' END AS QUADRAESPORTES,
	CASE WHEN SALA_GINASTICA = 'SIM' THEN 'S' ELSE 'N' END AS SALA_GINASTICA,
	CASE WHEN SALAO_FESTAS = 'SIM' THEN 'S' ELSE 'N' END AS SALAO_FESTAS,
	CASE WHEN PLAYGROUD = 'SIM' THEN 'S' ELSE 'N' END AS PLAYGROUND,
	CASE WHEN SALAO_BRINQUEDOS = 'SIM' THEN 'S' ELSE 'N' END AS SALA_BRINQUEDOS,
	CASE WHEN TV_A_CABO = 'SIM' THEN 'S' ELSE 'N' END AS TV_A_CABO,
	CASE WHEN MOBILIADO = 'SIM' THEN 'S' ELSE 'N' END AS MOBILIADO,
	CASE WHEN INTERFONE = 'SIM' THEN 'S' ELSE 'N' END AS INTERFONE,
	CASE WHEN PORTARIA_24HS = 'SIM' THEN 'S' ELSE 'N' END AS PORTARIA_24HS,
	CASE WHEN ACEITA_FINANCIAMENTO = 'SIM' THEN 'S' ELSE 'N' END AS ACEITA_FINANCIAMENTO,
	CASE WHEN ACEITA_PERMUTA = 'SIM' THEN 'S' ELSE 'N' END AS ACEITA_PERMUTA,
	SALDO_DEVEDOR,
	POSICAO,
	-- POSICAO_SOLAR,
	OCUPACAO AS OCUPANTE,
	CASE WHEN GERADOR_ENERGIA = 'SIM' THEN 'S' ELSE 'N' END AS GERADOR_ENERGIA,
	CASE WHEN POCO_ARTESIANO = 'SIM' THEN 'S' ELSE 'N' END AS POCO_ARTESIANO,
	CASE WHEN AGUA_ENCANADA= 'SIM' THEN 'S' ELSE 'N' END AS AGUA,
	CASE WHEN REDE_ESGOTO = 'SIM' THEN 'S' ELSE 'N' END AS ESGOTO,
	CASE WHEN ENERGIA_ELETRICA = 'SIM' THEN 'S' ELSE 'N' END AS ENERGIA,
     t.`GRUPO` AS classe, 
    t.`CATEGORIA` AS TIPO,
    '' AS sub_tipo,
    '' AS categoria 
	FROM cadimo i 
    LEFT JOIN `cadcat` t ON i.codigo_ct = t.CODIGO  
    where i.codigo_infoideias = $codigo_infoideias";

    return metodo_all($sql, 'migracao');
}


function imovel_proprietario($codigo_infoideias)
{
    $sql = "SELECT CADIMO.CODIGO AS id_imovel, CADIMO.CODIGO AS cod_imovel, CADIMO.CODIGO_C AS cod_cliente, CADCLI.NOME AS PROPRIETARIO 
            FROM CADIMO
            LEFT JOIN CDIMAG ON CDIMAG.CODIGO_O = CADIMO.CODIGO
            LEFT JOIN CADCLI ON CADCLI.CODIGO_C = CADIMO.CODIGO_C
            WHERE CADIMO.CODIGO_C <> 0 and CADIMO.codigo_infoideias = $codigo_infoideias 
            GROUP BY CADIMO.CODIGO";

    return metodo_all($sql, 'migracao');
}


function opcionistas_imovel($codigo_infoideias)
{
    // PLANILHA DE OPCIONISTAS DOS IMÓVEIS
    $sql = "SELECT CADIMO.CODIGO AS id_imovel,  CADIMO.CODIGO AS cod_imovel ,   
            CADEMP.CODIGO_D, CADEMP.NOME AS CORRETOR
            FROM CADIMO
            LEFT JOIN CDIMAG ON CDIMAG.CODIGO_O = CADIMO.CODIGO
            INNER JOIN CADEMP ON CADEMP.CODIGO_D = CDIMAG.CODIGO_D
            where CADIMO.codigo_infoideias = $codigo_infoideias";

    return metodo_all($sql, 'migracao');
}


function fotos_imovel($codigo_infoideias)
{
    // fotos
    $sql = "SELECT codigo AS id_imovel, codigo AS codigo_imovel, file_path AS url_foto, '' AS nome_foto, descricao AS legenda_foto, ordem,
            CASE WHEN VER_WEB = 'SIM' THEN 'S' ELSE 'N' END AS publica_no_site,
            CASE WHEN destaque_web = 'SIM' THEN 'S' ELSE 'N' END AS principal 
            FROM cdimim
            where cdimim.codigo_infoideias = $codigo_infoideias";

    return metodo_all($sql, 'migracao');
}


function usuarios_sistema($codigo_infoideias)
{
    // fotos
    $sql = "SELECT CODIGO_D, NOME, NOME_COMPLETO, EMAIL, CASE WHEN INATIVO = 'SIM' THEN 'N' ELSE 'S' END AS ativo, 
        CASE
            WHEN DIRETOR = 'Sim' THEN 'DIRETOR'
		    WHEN GERENTE = 'Sim' THEN 'GERENTE'
            WHEN CORRETOR = 'Sim' THEN 'CORRETOR'
            ELSE '' 
    	END AS CARGO
    	FROM cademp
        where cademp.codigo_infoideias = $codigo_infoideias";

    return metodo_all($sql, 'migracao');
}

function editarModelo()
{
    $modelo = $_GET['tabela'] ?? null;
    $codigo_infoideias = $_SESSION['company']['codigo_cliente'];

    $dadosDB = $modelo($codigo_infoideias);

    $sql = '';

    $relacaoTabelaId = [
        'clientes_proprietario' => 2,
        'imoveis' => 3,
        'imovel_proprietario' => 4,
        'opcionistas_imovel' => 5,
        'fotos_imovel' => 6,
        'usuarios_sistema' => 7,
        'corretores_clientes' => 8,
    ];

    $id_layout = $relacaoTabelaId[$modelo] ?? null;

    $sql = "SELECT * from layout_colunas  
            WHERE id_layout = $id_layout
            GROUP BY nome_exibicao
            ORDER BY posicao ASC";

    $modeloColunas = metodo_all($sql, 'migracao');

    return ['view' => 'vista/editarModelo', 'data' => ['modeloColunas' => $modeloColunas, 'dadosDB' => $dadosDB], 'function' => ''];
}

function processarArquivo($data)
{
    $codigo_infoideias = $_SESSION['company']['codigo_cliente'];

    $dadosDB = $data['tabela']($codigo_infoideias);

    $sql = '';

    $relacaoTabelaId = [
        'clientes_proprietario' => 2,
        'imoveis' => 3,
        'imovel_proprietario' => 4,
        'opcionistas_imovel' => 5,
        'fotos_imovel' => 6,
        'usuarios_sistema' => 7,
        'corretores_clientes' => 8,
    ];

    $id_layout = $relacaoTabelaId[$data['tabela']] ?? null;

    $sql = "SELECT * from layout_colunas  
            WHERE id_layout = $id_layout
            GROUP BY nome_exibicao
            ORDER BY posicao ASC";

    $modeloColunas = metodo_all($sql, 'migracao');

    // dd($modeloColunas, $dadosDB);
    $resultado = associaLayoutDados($modeloColunas, $dadosDB, $data['tabela']);


    // gerar arquivo excel. variavel $resultado é um array com os dados formatados conforme o layout sendo o indece 0 o header
    $nomeArquivo = $data['tabela'] . '_importacao.xlsx';
    gerarExcel($resultado, $nomeArquivo);

    return_api(200, 'Processamento concluido com sucesso', []);
}

function gerarExcel($resultado, $nomeArquivo)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($resultado as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 1);
            $sheet->setCellValue($cell, $value);
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $destinoDir = __DIR__ . "/../../assets/convertidos/{$_SESSION['company']['nome']}/{$modelo->nome_modelo}/" . $modelo->id_modelo . '/';

    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true)) {
        throw new \RuntimeException("Não foi possível criar pasta de destino: {$destinoDir}");
    }

    $caminhoFinal = $destinoDir . $_SESSION['company']['nome'] . '_' . $modelo->nome_modelo . "_" . $modelo->id_modelo . "_Todos.xlsx";
    $writer->save($caminhoFinal);
}

function associaLayoutDados($modeloColunas, $dadosDB, $tabela)
{
    $resultado = [];
    $header = [];

    foreach ($dadosDB as $dado) {
        $linha = [];

        foreach ($modeloColunas as $coluna) {
            if ($tabela == 'usuarios_sistema') {
                associacaoUsuariosSistema($coluna, $dado, $linha, $header);
            }
        }

        $resultado[] = $linha;
    }
    $resultado = array_merge([$header], $resultado);

    return $resultado;
}

function associacaoUsuariosSistema($coluna, $dado, &$linha, &$header)
{
    if ($coluna['nome_exibicao'] == 'id ou codigo do usuario' && isset($dado['CODIGO_D'])) {
        $linha[$coluna['posicao']] = $dado['CODIGO_D'];
        $header[$coluna['posicao']] = $coluna['nome_exibicao'];
        // 
    } elseif ($coluna['nome_exibicao'] == 'nome usuario' && isset($dado['NOME'])) {
        $linha[$coluna['posicao']] = $dado['NOME'];
        $header[$coluna['posicao']] = $coluna['nome_exibicao'];
        // 
    } elseif ($coluna['nome_exibicao'] == 'nome  completo' && isset($dado['NOME_COMPLETO'])) {
        $linha[$coluna['posicao']] = $dado['NOME_COMPLETO'];
        $header[$coluna['posicao']] = $coluna['nome_exibicao'];
        // 
    } elseif ($coluna['nome_exibicao'] == 'email' && isset($dado['EMAIL'])) {
        $linha[$coluna['posicao']] = $dado['EMAIL'];
        $header[$coluna['posicao']] = $coluna['nome_exibicao'];
        // 
    } elseif ($coluna['nome_exibicao'] == 'ativo' && isset($dado['ativo'])) {
        $linha[$coluna['posicao']] = $dado['ativo'];
        $header[$coluna['posicao']] = $coluna['nome_exibicao'];
        // 
    } elseif ($coluna['nome_exibicao'] == 'cargo' && isset($dado['CARGO'])) {
        $linha[$coluna['posicao']] = $dado['CARGO'];
        $header[$coluna['posicao']] = $coluna['nome_exibicao'];
        // 
    }
}
