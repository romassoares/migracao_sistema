<?php

// -------------------------------------------------------------------------------
function metodo_get($sql)
{
    global $clienteDB;

    if ($clienteDB->connect_error)
        retorno(500, false, 'Database connection error: ' . $clienteDB->connect_error);

    $query = $clienteDB->query($sql);

    if ($query === false)
        retorno(400, false, strval($clienteDB->error));

    return (object) $query->fetch_assoc();
}

// -------------------------------------------------------------------------------
function metodo_all($sql)
{
    global $clienteDB;

    if ($clienteDB->connect_error)
        retorno(500, false, 'Database connection error: ' . $clienteDB->connect_error);

    $query = $clienteDB->query($sql);

    if ($query === false)
        retorno(500, false, strval($clienteDB->error));

    return (object) $query->fetch_all(MYSQLI_ASSOC);
}

// -------------------------------------------------------------------------------
function insert_update($sql, $binds, $data)
{
    global $clienteDB;

    $stmt = $clienteDB->prepare($sql);

    if (!$stmt)
        retorno(500, false, 'Failed to prepare statement in insert or update: ' . $clienteDB->error);

    $params = [];
    foreach ($data as &$value)
        $params[] = &$value;

    array_unshift($params, $binds);
    call_user_func_array([$stmt, 'bind_param'], $params);

    if (!$stmt->execute())
        retorno(400, false, 'Failed to execute statement in insert update execute: ' . $stmt->error);

    if ($stmt->errno == 1062)
        retorno(200, false, 'Erro" O item já está cadastrado');

    $lastInsertId = $clienteDB->insert_id;

    $stmt->close();

    return $lastInsertId;
}

// -------------------------------------------------------------------------------

function retorno($status_code, $status_response, $msg_response)
{
    http_response_code($status_code);
    echo json_encode(['status' => $status_response, 'msg' => $msg_response]);
    exit;
}
