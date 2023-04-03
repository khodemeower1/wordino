<?php

namespace App\Controller;

use System\User;
use System\Http;
use System\DatabaseConnection;

/**
 * @OA\Info(title="Wordino API", version="1.0.0")
 */

class AuthorController
{

    /**
     * @OA\Post(
     *     path="/authors/changePassword",
     *     summary="Change the password for a user",
     *     tags={"Author"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Header(
     *         header="Authorization",
     *         description="Bearer token",
     *         @OA\Schema(
     *             type="string",
     *             format="Bearer JWT"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="oldPassword", type="string"),
     *             @OA\Property(property="newPassword", type="string"),
     *             @OA\Property(property="confirmPassword", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="description", type="string", example="password changed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="description", type="string", example="old password is not correct or new password and confirm password are not the same")
     *         )
     *     )
     * )
     **/

    public function changePassword($vars, $user)
    {
        $data = Http::jsonParamChecker(['oldPassword','newPassword','confirmPassword']);

        $conn = DatabaseConnection::getInstance();

        $old_pw_hash = md5($data['oldPassword'].User::get_salt());

        $old_pw_hash_in_db = $conn->get('authors','password',['id' => $user->id]);

        if ($old_pw_hash_in_db !== $old_pw_hash){
            rJSON(false,401,'old password is not correct');
        }

        if ($data['newPassword'] !== $data['confirmPassword']){
            rJSON(false,401,'new password and confirm password are not the same');
        }

        $conn->update('authors',[
            'password' => md5($data['newPassword'].User::get_salt())
        ],[
            'id' => $user->id
        ]);

        rJSON(true,200,'password changed successfully');

    }

    /**
     * @OA\Post(
     *     path="/authors/newAuthor",
     *     summary="Register new Author",
     *     description="This endpoint registers a new Author if the username is not already in use.",
     *     tags={"authentication","Author"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"username", "password"},
     *                 @OA\Property(
     *                     property="username",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="access_level",
     *                     type="string",
     *                     description="Optional access level for the new Author. default: 2"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=200
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="new user inserted to the database"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict !",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=409
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="user already exist in database!"
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Header(
     *         header="Authorization",
     *         description="Bearer token",
     *         @OA\Schema(
     *             type="string",
     *             format="Bearer JWT"
     *         )
     *     )
     * )
     */

    public function newAuthor($vals, $user)
    {
        $user->hasAccess(4);
        Http::paramChecker(['username', 'password']);
        $conn = DatabaseConnection::getInstance();
        if ($conn->has('authors', [
            'username' => $_REQUEST['username']
        ])) {
            rJSON(false, 409, 'user already exist in database!');
        }

        $new_user_array = [
            'username' => $_REQUEST['username'],
            'password' => md5($_REQUEST['password'] . User::get_salt())
        ];
        if (isset($_REQUEST['access_level']) && !empty($_REQUEST['access_level'])) {
            if ($_REQUEST['access_level'] > 4 || $_REQUEST['access_level'] < 1)
                rJSON(false, 400, 'access level must be between 1 and 4');
            $new_user_array['access_level'] = $_REQUEST['access_level'];
        } else {
            $new_user_array['access_level'] = 2;
        }
        $conn->insert('authors', $new_user_array);
        rJSON(true, 200, 'new user inserted to the database');
    }



    /**
     * @OA\Post(
     *     path="/authors/login",
     *     summary="Author Login",
     *     tags={"authentication","Author"},
     *     description="This endpoint authenticates the user and returns a JWT token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="is_ok",
     *                     type="boolean"
     *                 ),
     *                 @OA\Property(
     *                     property="code",
     *                     type="integer"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="result",
     *                     type="object",
     *                     @OA\Property(
     *                         property="jwt-token",
     *                         type="string"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=401
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="invalid credentials"
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * @OA\SecurityScheme(
     *     securityScheme="bearerAuth",
     *     type="http",
     *     scheme="bearer",
     *     bearerFormat="JWT"
     * )
     */
    public function login($vars)
    {
        $data = Http::jsonParamChecker(['username', 'password']);
        User::login($data['username'], $data['password']);
    }

    /**
     * @OA\Get(
     *     path="/authors/list",
     *     summary="Get list of Authors",
     *     description="This endpoint returns a list of all Authors in the database.",
     *     tags={"Author"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=200
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="Here is the list of Authors"
     *             ),
     *             @OA\Property(
     *                 property="result",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="The Author's ID."
     *                     ),
     *                     @OA\Property(
     *                         property="username",
     *                         type="string",
     *                         description="The Author's username."
     *                     ),
     *                     @OA\Property(
     *                         property="access_level",
     *                         type="integer",
     *                         description="The Author's access level."
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Header(
     *         header="Authorization",
     *         description="Bearer token",
     *         @OA\Schema(
     *             type="string",
     *             format="Bearer JWT"
     *         )
     *     )
     * )
     */

    public function list($vars, $user)
    {
        $user->hasAccess(3);
        $users = DatabaseConnection::getInstance()->select('authors', ['id', 'username', 'access_level']);
        rJSON(true, 200, 'here is users list', $users);
    }


    /**
     * @OA\Get(
     *     path="/authors/info/{id}",
     *     summary="Get Author Info",
     *     description="Get information about an author by their ID",
     *     tags={"Author"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the author to retrieve",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=200
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="user info"
     *             ),
     *             @OA\Property(
     *                 property="result",
     *                 type="object",
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     example="johndoe"
     *                 ),
     *                 @OA\Property(
     *                     property="total_word_count",
     *                     type="integer",
     *                     example=100
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=404
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="user not found"
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Header(
     *         header="Authorization",
     *         description="Bearer token",
     *         @OA\Schema(
     *             type="string",
     *             format="Bearer JWT"
     *         )
     *     )
     * )
     */

    public function info($vars, $user)
    {

        $user->hasAccess(3);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('authors', [
            'id' => $vars['id']
        ])) {
            rJSON(false, 404, 'user not found');
        }




        $username = $conn->get('authors', 'username', [
            'id' => $vars['id']
        ]);

        $total_word_count = $conn->count('word_author', [
            'author_id' => $vars['id']
        ]);

        rJSON(true, 200, 'user info', [
            'username' => $username,
            'total_word_count' => $total_word_count,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/authors/changeAccessLevel",
     *     summary="Change access level of an author",
     *     description="This endpoint allows an authorized user with access level 4 to change the access level of an author.",
     *     tags={"Author"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User ID and new access level",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"user_id", "access_level"},
     *                 @OA\Property(
     *                     property="user_id",
     *                     description="ID of the user to change access level for",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="access_level",
     *                     description="New access level for the user",
     *                     type="integer",
     *                     example=3
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Access level changed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=200
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="User access level changed to: 3"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Author not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=404
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="Author not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Access level does not exist",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="is_ok",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=409
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="Access level does not exist!"
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Header(
     *         header="Authorization",
     *         description="Bearer token",
     *         @OA\Schema(
     *             type="string",
     *             format="Bearer JWT"
     *         )
     *     )
     * )
     */

    public function changeAccessLevel($vars, $user)
    {
        $user->hasAccess(4);

        Http::paramChecker([
            'user_id',
            'access_level'
        ]);

        $conn = DatabaseConnection::getInstance();

        if ($_REQUEST['access_level'] > 4 or $_REQUEST['access_level'] < 0){
            rJSON(false,409,'access level does not exist!');
        }

        if ($conn->has('authors',['id' => $_REQUEST['user_id']])){
            $conn->update('authors', ['access_level' => $_REQUEST['access_level']], [
                'id' => $_REQUEST['user_id']
            ]);
            rJSON(true,200,'user access level changed to: '.$_REQUEST['access_level']);
        }else{
            rJSON(false,404,'author does not found!');
        }
    }

    /**
     * @OA\Post(
     *     path="/authors/removeUser",
     *     summary="Remove a user",
     *     description="This endpoint removes a user from the database based on their ID.",
     *     tags={"Author"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Header(
     *         header="Authorization",
     *         description="Bearer token",
     *         @OA\Schema(
     *             type="string",
     *             format="Bearer JWT"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="user_id",
     *                     description="The ID of the user to remove",
     *                     type="integer",
     *                 ),
     *                 required={
     *                     "user_id"
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="is_ok",
     *                 description="Indicates whether the operation was successful",
     *                 type="boolean"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 description="HTTP status code",
     *                 type="integer"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 description="A description of the response",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="is_ok",
     *                 description="Indicates whether the operation was successful",
     *                 type="boolean"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 description="HTTP status code",
     *                 type="integer"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 description="A description of the response",
     *                 type="string"
     *             )
     *         )
     *     )
     * )
     */

    public function removeUser($vars, $user)
    {
        $user->hasAccess(4);

        Http::paramChecker([
            'user_id'
        ]);

        $conn = DatabaseConnection::getInstance();

        if ($conn->has('authors', ['id' => $_REQUEST['user_id']])){
            $conn->delete('authors', ['id' => $_REQUEST['user_id']]);
            rJSON(true,200,'user removed successfully');
        }else{
            rJSON(false,404,'author does not found!');
        }
    }
}