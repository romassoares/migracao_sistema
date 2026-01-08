<?php
include_once __DIR__ . '/../../database/db.php';

$db = new DB();

function verifica_tabela_exist($tabela)
{
    // Escape the table name to prevent SQL injection
    $tabela = addslashes($tabela);

    $resultado = metodo_get("
        SELECT COUNT(*) AS count 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = '$tabela'
    ", 'migracao');

    // metodo_get provavelmente retorna um array associativo
    if (!empty($resultado) && isset($resultado->count) && $resultado->count > 0) {
        return true;
    }

    return false;
}

// -------------------------------------------------------------------------------
function metodo_get($sql, $database)
{
    global $db;

    $query = $db->connect($database)->query($sql);

    if ($query === false) {
        writeInFileLog($sql);
        die(strval($db->connect($database)->error));
    }

    return (object) $query->fetch_assoc();
}

// -------------------------------------------------------------------------------
function metodo_all($sql, $database)
{
    global $db;

    $query = $db->connect($database)->query($sql);

    if ($query === false) {
        writeInFileLog($sql);
        die(strval($db->connect($database)->error));
    }

    return $query->fetch_all(MYSQLI_ASSOC);
}

// -------------------------------------------------------------------------------
function insert_update($sql, $binds, $data, $database)
{
    global $db;

    $conn = $db->connect($database);

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        writeInFileLog($sql);
        writeInFileLog($binds);
        writeInFileLog($data);
        die('Failed to prepare statement in insert or update: ' . $conn->error);
    }

    $params = [];
    foreach ($data as &$value)
        $params[] = &$value;

    array_unshift($params, $binds);
    call_user_func_array([$stmt, 'bind_param'], $params);

    if (!$stmt->execute()) {
        // Checar erro de chave duplicada
        if ($stmt->errno == 1062)
            die('Erro: O item já está cadastrado');

        die('Failed to execute statement in insert update execute: ' . $stmt->error);
    }

    $lastInsertId = $conn->insert_id;

    $stmt->close();

    return $lastInsertId;
}

// -------------------------------------------------------------------------------

function metodo_exec($sql, $database)
{
    global $db;

    $query = $db->connect($database)->query($sql);

    if ($query === false) {
        writeInFileLog($sql);
        die(strval($db->connect($database)->error));
    }

    return;
}


function multi_query_db($sql, $database = 'migracao')
{
    global $db;

    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = $db->connect($database ?: 'migracao');

    if (!$conn) {
        writeInFileLog("Erro ao conectar ao banco: {$database}" . PHP_EOL);
        echo "Erro: não foi possível conectar ao banco '{$database}'" . PHP_EOL;
        return false;
    }

    $conn->begin_transaction();

    try {
        $conn->query("SET sql_mode = ''");

        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());

            if ($conn->errno) {
                writeInFileLog("Erro SQL inicial: " . $conn->error . PHP_EOL);
                throw new Exception($conn->error);
            }

            $conn->commit();
            return true;
        } else {
            writeInFileLog("Erro SQL inicial: " . $conn->error . PHP_EOL);
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();

        writeInFileLog("Exceção: " . $sql . PHP_EOL);
        echo "Erro: " . $e->getMessage() . PHP_EOL;

        return false;
    }
}
