<?php

namespace System;

class Http{
    public static function paramChecker($params){
        foreach ($params as $param){
            if (!(isset($_REQUEST[$param]) && !empty($_REQUEST[$param]))){
                rJSON(false,400,'please send all of the parameter needed!');
            }
        }
    }
    public static function jsonParamChecker($params){
        $request_body = file_get_contents('php://input');
        try {
            $data = json_decode($request_body, true);
        }catch (\Exception $e){
            rJSON(false, 400, 'json is not valid!');
        }
        if ($data == null){
            rJSON(false, 400, 'json is not valid!');
        }

        foreach($params as $param){
            if (!isset($data[$param])){
                rJSON(false,400,'please send all of the parameter needed!');
            }
        }
        return $data;
    }
}
