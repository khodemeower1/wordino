<?php

namespace App\Controller;

use System\Http;
use System\DatabaseConnection;

class WordlistController
{


    /**
     * @OA\Post(
     *     path="/wordlists/newWordlist",
     *     summary="Create a new wordlist",
     *     tags={"Wordlists"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="my_wordlist"),
     *             @OA\Property(property="group_id", type="integer", example="13")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="New wordlist successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example="200"),
     *             @OA\Property(property="description", type="string", example="new wordlist inserted to database!"),
     *             @OA\Property(property="result", type="object",
     *                 @OA\Property(property="wordlist_name", type="string", example="my_wordlist"),
     *                 @OA\Property(property="wordlist_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Group not found in database",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example="404"),
     *             @OA\Property(property="description", type="string", example="Group not found in database")
     *         )
     *     ),
     *     @OA\Response(
     *         response="409",
     *         description="Wordlist already exists in database",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example="409"),
     *             @OA\Property(property="description", type="string", example="Wordlist already exists in database")
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
     *     ),
     * )
     */


    public function newWordlist($vars, $user)
    {
        $user->hasAccess(3);
        $data = Http::jsonParamChecker(['name']);
        $conn = DatabaseConnection::getInstance();
        if ($conn->has('wordlists', [
            'wordlist_name' => $data['name']
        ])) {
            rJSON(false, 409, 'wordlist already exist in database!');
        }
        $is_group_sended = (isset($data['group_id']) && !empty($data['group_id']));

        if($is_group_sended){
            if(!$conn->has('groups', [
                'id' => $data['group_id']
            ])){
                rJSON(false, 404, 'group not found in database!');
            }
        }

        $is_ok = true;
        $wordlist_id = 0;

        $conn->action(function ($conn) use ($data, $is_group_sended, &$is_ok, &$wordlist_id) {
            $conn->insert('wordlists', [
                'wordlist_name' => $data['name']
            ]);

            $wordlist_id = $conn->id();

            if($is_group_sended){
                $conn->insert('wordlist_group', [
                    'group_id' => $data['group_id'],
                    'wordlist_id' => $wordlist_id
                ]);
                if (!$conn->has('wordlist_group', [
                    'group_id' => $data['group_id'],
                    'wordlist_id' => $wordlist_id
                ])){
                    $is_ok = false;
                    return false;
                }
            }
        });

        if (!$is_ok)
            rJSON(false, 500, 'internal server error!');

        rJSON(true, 200, 'new wordlist inserted to database!', [
            'wordlist_name' => $data['name'],
            'wordlist_id' => $wordlist_id
        ]);
    }




    /**
     * @OA\Get(
     *     path="/wordlists/getWordlist/{id}",
     *     tags={"Wordlists"},
     *     summary="Get words of a specific wordlist",
     *     description="Get a list of words of a specific wordlist in descending order by their points.",
     *     operationId="getWordlist",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the wordlist to retrieve",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="wordlist not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="description", type="string", example="wordlist not found in database!")
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

    public function getWordlist($vars, $user)
    {
        $user->hasAccess(2);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('wordlists', [
            'id' => $vars['id']
        ])) {
            rJSON(false, 404, 'wordlist not found in database!');
        }
        $wordlist = $conn->query("SELECT words.word, words.points, words.vuln, words.reference
FROM wordlists, word_wordlist, words
WHERE wordlists.id = word_wordlist.wordlist_id
AND words.id = word_wordlist.word_id
AND wordlists.id = :id
ORDER BY words.points DESC;",
            [
                ':id' => $vars['id']
            ])->fetchAll();
        foreach($wordlist as $w_key => $w_value){
            unset($wordlist[$w_key]['0']);
            unset($wordlist[$w_key]['1']);
            unset($wordlist[$w_key]['2']);
            unset($wordlist[$w_key]['3']);
        }
        echo json_encode($wordlist, JSON_PRETTY_PRINT);
        die();
    }


    /**
     * @OA\Get(
     *     path="/wordlists/info/{id}",
     *     summary="Get information about a wordlist",
     *     description="Retrieve information about a wordlist, including the total number of words, the authors who have contributed to the wordlist and the groups it belongs to.",
     *     operationId="getWordlistInfo",
     *     tags={"Wordlists"},
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The ID of the wordlist to retrieve information about",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="description", type="string", example="here is your information :D!"),
     *        @OA\Property (
     *          property="result",
     *          type="array",
     *        @OA\Items(
     *             @OA\Property(
     *                 property="total_words",
     *                 type="integer",
     *                 description="The total number of words in the wordlist"
     *             ),
     *             @OA\Property(
     *                 property="wordlist_name",
     *                 type="string",
     *                 description="The name of the wordlist"
     *             ),
     *             @OA\Property(
     *                 property="authors",
     *                 type="array",
     *                 description="An array of authors who have contributed to the wordlist",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="username",
     *                         type="string",
     *                         description="The username of the author"
     *                     ),
     *                     @OA\Property(
     *                         property="word_count",
     *                         type="integer",
     *                         description="The number of words the author has contributed to the wordlist"
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="groups",
     *                 type="array",
     *                 description="An array of groups that the wordlist belongs to",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="The ID of the group"
     *                     ),
     *                     @OA\Property(
     *                         property="group_name",
     *                         type="string",
     *                         description="The name of the group"
     *                     ),
     *                     @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         description="The description of the group"
     *                     )
     *                 )
     *             )
     *        ))
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="wordlist not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="description", type="string", example="wordlist not found in database!")
     *         )
     *     ),
     * )
     */

    public function info($vars, $user)
    {
        $user->hasAccess(1);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('wordlists', [
            'id' => $vars['id']
        ])) {
            rJSON(false, 404, 'wordlist not found in database!');
        }
        $total_words = $conn->count('word_wordlist', [
            'wordlist_id' => $vars['id']
        ]);
        $authorz = $conn->query("SELECT authors.username, COUNT(DISTINCT words.id) AS word_count
FROM authors
JOIN word_author ON authors.id = word_author.author_id
JOIN words ON word_author.word_id = words.id
JOIN word_wordlist ON words.id = word_wordlist.word_id
JOIN wordlists ON word_wordlist.wordlist_id = wordlists.id
WHERE wordlists.id = :id
GROUP BY authors.username;", [
            ':id' => $vars['id']
        ])->fetchAll();
        $authors = [];
        foreach ($authorz as $author) {
            $authors[] = [
                'username' => $author['username'],
                'word_count' => $author['word_count']
            ];
        }

        $groups = $conn->query("SELECT groups.id, groups.group_name, groups.description
FROM groups, wordlist_group
WHERE groups.id = wordlist_group.group_id
AND wordlist_group.wordlist_id = :id", [
            ':id' => $vars['id']
        ])->fetchAll();

        $grps = [];

        foreach ($groups as $group){
            $grps[] = [
                'id' => $group['id'],
                'group_name' => $group['group_name'],
                'description' => $group['description']
            ];
        }

        $wl = $conn->get('wordlists', [
            'wordlist_name'
        ], [
            'id' => $vars['id']
        ]);

        rJSON(true, 200, 'wordlist info', [
            'total_words' => $total_words,
            'wordlist_name' => $wl['wordlist_name'],
            'authors' => $authors,
            'groups' => $grps
        ]);

    }


    /**
     * @OA\Post(
     *     path="/wordlists/addWordlistToGroup",
     *     summary="Add a wordlist to a group",
     *     description="Adds a wordlist to a group in the database",
     *     operationId="addWordlistToGroup",
     *     tags={"Wordlists"},
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
     *             @OA\Property(property="wordlist_id", type="integer", example=1),
     *             @OA\Property(property="group_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="something not found!",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="description", type="string", example="something not found!")
     *         )
     *     ),
     *     @OA\Response(
     *         response="409",
     *         description="conflict",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=409),
     *             @OA\Property(property="description", type="string", example="wordlist already added to group!")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="done",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="description", type="string", example="wordlist added!"),
     *             @OA\Property(
     *                 property="result",
     *                 type="array",
     *                 @OA\Items(
     *                      @OA\Property(property="wordlist_id", type="integer", example=1),
     *                      @OA\Property(property="group_id", type="integer", example=1),
     *                 )
     *              )
     *          )
     *      )
     *    )
     */

    public function addWordlistToGroup($vars, $user)
    {
        $user->hasAccess(3);
        $data = Http::jsonParamChecker(['wordlist_id', 'group_id']);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('wordlists', [
            'id' => $data['wordlist_id']
        ])) {
            rJSON(false, 404, 'wordlist not found in database!');
        }
        if (!$conn->has('groups', [
            'id' => $data['group_id']
        ])) {
            rJSON(false, 404, 'group not found in database!');
        }
        if ($conn->has('wordlist_group', [
            'AND' => [
                'wordlist_id' => $data['wordlist_id'],
                'group_id' => $data['group_id']
            ]
        ])) {
            rJSON(false, 409, 'wordlist already added to group!');
        }
        $conn->insert('wordlist_group', [
            'wordlist_id' => $data['wordlist_id'],
            'group_id' => $data['group_id']
        ]);
        rJSON(true, 200, 'wordlist added to group', [
            'wordlist_id' => $data['wordlist_id'],
            'group_id' => $data['group_id']
        ]);
    }


    /**
     * @OA\Post(
     *     path="/wordlists/removeWordlistFromGroup",
     *     summary="Removes a wordlist from a group",
     *     tags={"Wordlists"},
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
     *             @OA\Property(property="wordlist_id", type="integer", example=1),
     *             @OA\Property(property="group_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Wordlist successfully removed from the group",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="description", type="string", example="Wordlist successfully removed from the group"),
     *             @OA\Property(
     *                 property="result",
     *                 type="array",
     *                 @OA\Items(
     *                      @OA\Property(
     *                          property="wordlist_id",
     *                          type="integer",
     *                          description="ID of the removed wordlist"
     *                      ),
             *             @OA\Property(
             *                 property="group_id",
             *                 type="integer",
             *                 description="ID of the group from which the wordlist was removed"
             *             )
     *               )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Wordlist or group not found in the database"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Wordlist is not added to the group"
     *     ),

     * ))
     */

    public function removeWordlistFromGroup($vars, $user)
    {
        $user->hasAccess(3);
        $data = Http::jsonParamChecker(['wordlist_id', 'group_id']);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('wordlists', [
            'id' => $data['wordlist_id']
        ])) {
            rJSON(false, 404, 'wordlist not found in database!');
        }
        if (!$conn->has('groups', [
            'id' => $data['group_id']
        ])) {
            rJSON(false, 404, 'group not found in database!');
        }
        if (!$conn->has('wordlist_group', [
            'AND' => [
                'wordlist_id' => $data['wordlist_id'],
                'group_id' => $data['group_id']
            ]
        ])) {
            rJSON(false, 409, 'wordlist is not added to group!');
        }
        $conn->delete('wordlist_group', [
            'AND' => [
                'wordlist_id' => $data['wordlist_id'],
                'group_id' => $data['group_id']
            ]
        ]);
        rJSON(true, 200, 'wordlist removed from group', [
            'wordlist_id' => $data['wordlist_id'],
            'group_id' => $data['group_id']
        ]);
    }






    /**
     * @OA\Get(
     *     path="/wordlists/list",
     *     summary="Get a list of all wordlists",
     *     tags={"Wordlists"},
     *     @OA\Response(
     *         response=200,
     *         description="A list of all wordlists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="description", type="string", example="A list of all wordlists"),
     *             @OA\Property(
     *                 property="result",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="The ID of the wordlist"
     *                     ),
     *                     @OA\Property(
     *                         property="wordlist_name",
     *                         type="string",
     *                         description="The name of the wordlist"
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

    public function list($vars, $user)
    {
        $user->hasAccess(1);
        $conn = DatabaseConnection::getInstance();
        $wordlists = $conn->select('wordlists', [
            'id',
            'wordlist_name'
        ]);
        rJSON(true, 200, 'here is a list of wordlists', $wordlists);
    }



    /**
     * @OA\Delete(
     *     path="/wordlists/delete/{id}",
     *     summary="Delete a wordlist by ID",
     *     tags={"Wordlists"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the wordlist to delete",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *             minimum=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Wordlist deleted successfully",
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
     *                 example="Wordlist deleted"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Wordlist not found in database",
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
     *                 example="Wordlist not found in database"
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
     *     ),
     * )
     */

    public function delete($vars, $user)
    {
        $user->hasAccess(3);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('wordlists', [
            'id' => $vars['id']
        ])) {
            rJSON(false, 404, 'wordlist not found in database!');
        }

        $is_ok = true;

        $conn->action(function ($conn) use ($vars, &$is_ok) {
            $conn->delete('wordlists', [
                'id' => $vars['id']
            ]);

            if ($conn->has('wordlists',[
                'id' => $vars['id']
            ])){
                $is_ok = false;
                return false;
            }


            $conn->delete('wordlist_group', [
                'wordlist_id' => $vars['id']
            ]);

            if ($conn->has('wordlist_group', [
                'wordlist_id' => $vars['id']
            ])){
                $is_ok = false;
                return false;
            }

            $conn->delete('word_wordlist', [
                'wordlist_id' => $vars['id']
            ]);

            if ($conn->has('word_wordlist', [
                'wordlist_id' => $vars['id']
            ])){
                $is_ok = false;
                return false;
            }

        });

        if (!$is_ok)
            rJSON(false, 500, 'wordlist could not be deleted');

        rJSON(true, 200, 'wordlist deleted');
    }


    /**
     * @OA\Post(
     *     path="/wordlists/rename",
     *     summary="Rename a wordlist",
     *     tags={"Wordlists"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Provide the ID and new name for the wordlist.",
     *         @OA\JsonContent(
     *             @OA\Property(property="wordlist_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="New name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Wordlist renamed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="description", type="string", example="Wordlist renamed"),
     *             @OA\Property(
     *                 property="result",
     *                 type="object",
     *                 @OA\Property(
     *                     property="wordlist_id",
     *                     type="integer",
     *                     description="The ID of the renamed wordlist"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="The new name of the wordlist"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Wordlist not found in database",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="description", type="string", example="Wordlist not found in database!")
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
     *     ),
     * )
     */

    public function rename($vars, $user)
    {
        $user->hasAccess(3);
        $data = Http::jsonParamChecker(['wordlist_id', 'name']);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('wordlists', [
            'id' => $data['wordlist_id']
        ])) {
            rJSON(false, 404, 'wordlist not found in database!');
        }
        $conn->update('wordlists', [
            'wordlist_name' => $data['name']
        ], [
            'id' => $data['wordlist_id']
        ]);
        rJSON(true, 200, 'wordlist renamed', [
            'wordlist_id' => $data['wordlist_id'],
            'name' => $data['name']
        ]);
    }


    /**
     * @OA\Get(
     *     path="/wordlists/getWordlistFile/{id}",
     *     tags={"Wordlists"},
     *     summary="Get words file of a specific wordlist",
     *     description="Get a list of words of a specific wordlist in descending order by their points.",
     *     operationId="getWordlistFile",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the wordlist to retrieve",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="wordlist not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="description", type="string", example="wordlist not found in database!")
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

    public function getWordlistFile($vars, $user)
    {
        $user->hasAccess(2);
        $conn = DatabaseConnection::getInstance();
        if (!$conn->has('wordlists', [
            'id' => $vars['id']
        ])) {
            rJSON(false, 404, 'wordlist not found in database!');
        }
        $wordlist = $conn->query("SELECT words.word
FROM wordlists, word_wordlist, words
WHERE wordlists.id = word_wordlist.wordlist_id
AND words.id = word_wordlist.word_id
AND wordlists.id = :id
ORDER BY words.points DESC;",
            [
                ':id' => $vars['id']
            ])->fetchAll();

        $random_name = rand(10000,99999);
        $file_addr = HOME.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$random_name.".txt";
        $file = fopen($file_addr, "w");

        foreach ($wordlist as $value) {
            fwrite($file, $value['word'] . "\n");
        }
        fclose($file);
        $wordlist_name = $conn->get('wordlists', 'wordlist_name', [
            'id' => $vars['id']
        ]);


        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($file_addr));
        header('Content-Disposition: inline; filename="' . $wordlist_name . '.txt"');
        readfile($file_addr);
        unlink($file_addr);
        die();
    }
    
}