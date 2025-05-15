<?php
function trata_json_request($json)
{

    $data1 = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => false, 'msg' => 'Invalid JSON']);
        exit;
    }

    if (!is_array($data1)) {
        if (is_object($data1)) {
            $data1 = (array) $data1;
        } else {
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'Invalid data format']);
            exit;
        }
    }
    $request = [];

    foreach ($data1 as $key => $value) {
        $request[$key] = filter_var($value, FILTER_DEFAULT);
    }

    return (object) $request;
}
