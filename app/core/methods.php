<?php
include_once __DIR__ . '/../../database/db.php';

$db = new DB();

// -------------------------------------------------------------------------------
function metodo_get($sql, $database)
{
    global $db;

    $query = $db->connect($database)->query($sql);

    if ($query === false)
        die(strval($db->connect($database)->error));

    return (object) $query->fetch_assoc();
}

// -------------------------------------------------------------------------------
function metodo_all($sql, $database)
{
    global $db;

    $query = $db->connect($database)->query($sql);

    if ($query === false)
        die(strval($db->connect($database)->error));

    return (object) $query->fetch_all(MYSQLI_ASSOC);
}

// -------------------------------------------------------------------------------
function insert_update($sql, $binds, $data, $database)
{
    global $db;

    // Obter uma única instância da conexão
    $conn = $db->connect($database);

    $stmt = $conn->prepare($sql);

    if (!$stmt)
        die('Failed to prepare statement in insert or update: ' . $conn->error);

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

    $lastInsertId = $conn->insert_id; // Agora está usando a mesma conexão da execução

    $stmt->close();

    return $lastInsertId;
}

// -------------------------------------------------------------------------------
