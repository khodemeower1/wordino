<?php

namespace System;

use Medoo\Medoo;

class DatabaseConnection{
    private static $instance = null;
    private function __construct(){}
    public static function getInstance(){
        if (self::$instance == null){
            try {
                self::$instance = new Medoo([
                    'type' => 'mysql',
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'username' => DB_USER,
                    'password' => DB_PASS,
                ]);
            }catch (\Exception $e){
                rJSON(false,503,'Database Connection Error');
            }
        }
        return self::$instance;
    }
}