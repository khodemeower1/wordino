<?php

function rJSON($is_ok,$code,$description = null,$result = null){
    $arr = [
        'is_ok' => $is_ok,
        'code' => $code,
        'description' => $description,
    ];
    if ($result != null){
        $arr['result'] = $result;
    }
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($arr);
    die();
}