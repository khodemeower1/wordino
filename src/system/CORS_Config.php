<?php

function cors($allowed_methods = null) {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $origin = $_SERVER['HTTP_ORIGIN'];
        if (in_array($origin, TRUSTED_DOMAINS)) {
            header('Access-Control-Allow-Origin: ' . $origin );
        }
    }
    if($allowed_methods != null){
        $allowed_methods_text = implode(', ', $allowed_methods);
        header('Access-Control-Allow-Methods: ' . $allowed_methods_text);
    }
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}