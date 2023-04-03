<?php

namespace App\Controller;

use System\Http;
use System\DatabaseConnection;

class GroupController
{


    /**
     * @OA\Post(
     *     path="/groups/newGroup",
     *     summary="Create a new group",
     *     tags={"Groups"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Name of the group",
     *                     example="My Group"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     description="Description of the group",
     *                     example="This is my group"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="New group inserted to database",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property (property="description", type="string", example="New group inserted to database"),
     *             @OA\Property(
     *                 property="result",
     *                 type="object",
     *                 @OA\Property(property="group_name", type="string", example="My Group"),
     *                 @OA\Property(property="group_id", type="integer", example=1)
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

    public function newGroup($vars, $user)
    {
        $user->hasAccess(4);
        Http::paramChecker(['name']);
        $conn = DatabaseConnection::getInstance();
        if ($conn->has('groups', [
            'group_name' => $_REQUEST['name']
        ])) {
            rJSON(false, 409, 'group already exist in database!');
        }
        if (isset($_REQUEST["description"]) && !empty($_REQUEST["description"]))
            $conn->insert('groups', [
                'group_name' => $_REQUEST['name'],
                'description' => $_REQUEST['description']
            ]);
        else
            $conn->insert('groups', [
                'group_name' => $_REQUEST['name']
            ]);

        rJSON(true, 200, 'new group inserted to database', [
            'group_name' => $_REQUEST['name'],
            'group_id' => $conn->id()
        ]);
    }



    /**
     * @OA\get(
     *     path="/groups/removeGroup/{id}",
     *     summary="Remove a group",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the group to remove",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group removed from database",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property (property="description", type="string", example="Group removed from database")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Group not found in database",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="description", type="string", example="Group not found in database")
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

    public function removeGroup($vars, $user)
    {
        $user->hasAccess(4);
        $conn = DatabaseConnection::getInstance();

        if (!$conn->has('groups', [
            'id' => $vars['id']
        ])) {
            rJSON(false, 404, 'group not found in database!');
        }

        $is_ok = true;

        $conn->action(function ($conn) use ($vars, &$is_ok) {

            $conn->delete('groups', [
                'id' => $vars['id']
            ]);

            if ($conn->has('groups', [
                'id' => $vars['id']
            ])) {
                $is_ok = false;
                return false;
            }

            $conn->delete('wordlist_group', [
                'group_id' => $vars['id']
            ]);

            if ($conn->has('wordlist_group', [
                'group_id' => $vars['id']
            ])) {
                $is_ok = false;
                return false;
            }

        });

        if (!$is_ok)
            rJSON(false, 500, 'error while removing group from database!');

        rJSON(true, 200, 'group removed from database');
    }


    /**
     * @OA\Get(
     *     path="/groups/list",
     *     summary="Get a list of all groups",
     *     tags={"Groups"},
     *     @OA\Response(
     *         response=200,
     *         description="List of all groups",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property (property="description", type="string", example="Groups list"),
     *             @OA\Property(
     *                 property="result",
     *                 type="object",
     *                 @OA\Property(
     *                     property="groups",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="group_name", type="string", example="My Group"),
     *                         @OA\Property(property="description", type="string", example="This is my group")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="group not found",
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
        $user->hasAccess(1);
        $conn = DatabaseConnection::getInstance();
        $groups = $conn->select('groups', [
            'id',
            'group_name',
            'description'
        ]);
        rJSON(true, 200, 'groups list', [
            'groups' => $groups
        ]);
    }



    /**
     * @OA\Get(
     *     path="/groups/info/{id}",
     *     summary="Get group information",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Group ID",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group information retrieved successfully",
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
     *                 example="group info"
     *             ),
     *             @OA\Property(
     *                 property="result",
     *                 type="object",
     *                 @OA\Property(
     *                     property="group",
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="group_name",
     *                         type="string",
     *                         example="My Group"
     *                     ),
     *                     @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         example="This is my group"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="total_wordlists",
     *                     type="integer",
     *                     example=3
     *                 ),
     *                 @OA\Property(
     *                     property="total_words",
     *                     type="integer",
     *                     example=100
     *                 ),
     *                 @OA\Property(
     *                     property="wordlists",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(
     *                             property="wordlist_id",
     *                             type="integer",
     *                             example=1
     *                         ),
     *                         @OA\Property(
     *                             property="wordlist_name",
     *                             type="string",
     *                             example="My Wordlist 1"
     *                         )
     *                     )
     *                 )
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
        $user->hasAccess(1);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('groups', [
            'id' => $vars['id']
        ])) {
            rJSON(false, 404, 'group not found in database!');
        }
        $group = $conn->select('groups', [
            'id',
            'group_name',
            'description'
        ], [
            'id' => $vars['id']
        ]);

        $total_wordlists = $conn->query("SELECT COUNT(*) as total_wordlists 
FROM wordlists, wordlist_group 
WHERE wordlists.id = wordlist_group.wordlist_id 
AND wordlist_group.group_id = :id", [
            ':id' => $vars['id']
        ])->fetch();

        $wordlists_id = $conn->query("SELECT wordlists.id as wordlist_id 
FROM wordlists, wordlist_group 
WHERE wordlists.id = wordlist_group.wordlist_id 
AND wordlist_group.group_id = :id", [
            ':id' => $vars['id']
        ])->fetchAll();

        $total_worlds = 0;

        foreach ($wordlists_id as $value){
            $total_words = $conn->query("SELECT COUNT(*) as total_words
FROM wordlists, word_wordlist, words
WHERE wordlists.id = word_wordlist.wordlist_id
AND words.id = word_wordlist.word_id
AND wordlists.id = :id;",
            [
                ':id' => $value['wordlist_id']
            ])->fetch();
            $total_worlds += $total_words['total_words'];
        }


        $wordlists = $conn->query("SELECT wordlists.id as wordlist_id, wordlists.wordlist_name
FROM wordlists, wordlist_group
WHERE wordlists.id = wordlist_group.wordlist_id
AND wordlist_group.group_id = :id;",[
            ':id' => $vars['id']
        ])->fetchAll();
        $arr = [];
        $counter = 0;
        foreach ($wordlists as $value){
            $arr[$counter]['wordlist_id'] = $value['wordlist_id'];
            $arr[$counter]['wordlist_name'] = $value['wordlist_name'];
            $counter++;
        }




        rJSON(true, 200, 'group info', [
            "group" => $group[0],
            "total_wordlists" => $total_wordlists['total_wordlists'],
            "total_words" => $total_worlds,
            "wordlists" => $arr
        ]);
    }


    /**
     * @OA\Get(
     *     path="/groups/getWords/{id}",
     *     summary="Get words for a group",
     *     description="Retrieve a list of distinct words associated with a group and order them by points.",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the group to retrieve words for.",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Group not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Server Error"
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

    public function getWords($vars, $user)
    {
        $user->hasAccess(2);
        $conn = DatabaseConnection::getInstance();

        if (!$conn->has('groups', [
            'id' => $vars['id']
        ])) {
            rJSON(false, 404, 'group not found in database!');
        }

        $wordlists_id = $conn->query("SELECT DISTINCT(words.word) 
FROM words, word_wordlist, wordlist_group 
WHERE 
    wordlist_group.wordlist_id = word_wordlist.wordlist_id 
    AND word_wordlist.word_id = words.id 
    AND wordlist_group.group_id = :id
ORDER BY words.points DESC;", [
            ':id' => $vars['id']
        ])->fetchAll();

        foreach($wordlists_id as $value){
            echo $value['word']."\n";
        }
        die();
    }

}