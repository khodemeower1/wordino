<?php

namespace System;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User
{
    private $conn;

    public static function get_jwt_key(){
        return JWT_KEY;
    }

    public static function get_salt(){
        return SALT;
    }

    //user information
    public $id;
    public $username;
    public $access_level;

    private function __construct($username)
    {
        $this->conn = DatabaseConnection::getInstance();
        $user_info = $this->conn->select('authors',['id','username','access_level'],[
            'username' => $username
        ]);
        $this->id = $user_info[0]['id'];
        $this->username = $user_info[0]['username'];
        $this->access_level = $user_info[0]['access_level'];
    }

    public static function login($username, $password)
    {
        $conn = DatabaseConnection::getInstance();
        if (
            $conn->has('authors',[
                "AND" => [
                    "username" => $username,
                    "password" => md5($password.self::get_salt())
                ]
            ])
        ){
            $expire_at     = time()+604800 ; //1 week
            $request_data = [
                'exp'  => $expire_at,
                'user' => $username,
            ];
            $jwt = JWT::encode($request_data,self::get_jwt_key(),'HS512');
            rJSON(true,200,'you logged in',[
                'jwt-token' => $jwt
            ]);
        }
        rJSON(false,401,'invalid credentials');
    }


    public static function isAuthenticated($jwt)
    {
        try {
            $token = JWT::decode($jwt, new Key(self::get_jwt_key(), 'HS512'));
        } catch (\Exception $e) {
            rJSON(false, 401, 'unAuthorized request');
        }
        if ($token->exp < time()){
            rJSON(false, 401, 'unAuthorized request');
        }
        return new User($token->user);
    }

    public function hasAccess($access_needed){
        if ($access_needed > $this->access_level){
            rJSON(false, 403, 'you dont have access to this resource !');
        }
    }

}