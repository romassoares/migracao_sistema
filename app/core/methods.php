<?php
include_once './database/db.php';

$db = new DB();

// -------------------------------------------------------------------------------
function metodo_get($sql, $database)
{
    global $db;

    $query = $db->connect($database)->query($sql);

    if ($query === false)
        retorno(400, false, strval($db->connect($database)->error));

    return (object) $query->fetch_assoc();
}

// -------------------------------------------------------------------------------
function metodo_all($sql, $database)
{
    global $db;

    if ($db->connect($database)->connect_error)
        die('Database connection error: ' . $db->connect($database)->connect_error);

    $query = $db->connect($database)->query($sql);

    if ($query === false)
        die(strval($db->connect($database)->error));

    return (object) $query->fetch_all(MYSQLI_ASSOC);
}

// -------------------------------------------------------------------------------
function insert_update($sql, $binds, $data, $database)
{
    global $db;

    $stmt = $db->connect($database)->prepare($sql);

    if (!$stmt)
        die('Failed to prepare statement in insert or update: ' . $db->connect($database)->error);

    $params = [];
    foreach ($data as &$value)
        $params[] = &$value;

    array_unshift($params, $binds);
    call_user_func_array([$stmt, 'bind_param'], $params);

    if (!$stmt->execute())
        retorno(400, false, 'Failed to execute statement in insert update execute: ' . $stmt->error);

    if ($stmt->errno == 1062)
        retorno(200, false, 'Erro" O item já está cadastrado');

    $lastInsertId = $db->connect($database)->insert_id;

    $stmt->close();

    return $lastInsertId;
}

// -------------------------------------------------------------------------------
