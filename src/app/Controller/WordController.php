<?php

namespace App\Controller;

use System\Http;
use System\DatabaseConnection;

class wordcontroller
{


    /**
     * @OA\Post(
     *     path="/words/delete",
     *     tags={"Words"},
     *     summary="Delete multiple words",
     *     description="Allows an authorized user with access level 3 to delete multiple words",
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
     *         description="Array of word IDs to be deleted",
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="integer",
     *                 format="int64"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Words deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="is_ok", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="description", type="string", example="Words deleted successfully"),
     *             @OA\Property(
     *                 property="result",
     *                 type="object",
     *                 @OA\Property(
     *                     property="word_id_not_int",
     *                     type="integer",
     *                     description="the word id is not an integer"
     *                 ),
     *                 @OA\Property(
     *                     property="word_id_not_exist",
     *                     type="integer",
     *                     description="The word id does not exist"
     *                 ),
     *                 @OA\Property(
     *                     property="word_id_deleted_successfull",
     *                     type="integer",
     *                     description="The word id was deleted successfully"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request body or JSON format"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     )
     * )
     */

    public function delete($vars, $user)
    {
        $user->hasAccess(3);

        $request_body = file_get_contents('php://input');
        try {
            $data = json_decode($request_body, true);
        }catch (\Exception $e){
            rJSON(false, 400, 'json is not valid!');
        }

        if ($data == null){
            rJSON(false, 400, 'json is not valid!');
        }

        $word_id_not_int = 0;
        $word_id_not_exist = 0;
        $word_id_deleted_successfull = 0;

        foreach($data as $id){

            if (!is_numeric($id)){
                $word_id_not_int++;
                continue;
            }

            if ($this->word_id_exist($id)){
                $this->deleteWord($id);
                $word_id_deleted_successfull++;
            } else {
                $word_id_not_exist++;
                continue;
            }

        }

        rJSON(true,200,'words deleted!',[
            'word_id_not_int' => $word_id_not_int,
            'word_id_not_exist' => $word_id_not_exist,
            'word_id_deleted_successfull' => $word_id_deleted_successfull
        ]);

    }

    private function deleteWord($id)
    {
        $conn = DatabaseConnection::getInstance();
        $conn->delete('word_wordlist', [
            'word_id' => $id
        ]);
        $conn->delete('word_author', [
            'word_id' => $id
        ]);
        $conn->delete('words', [
            'id' => $id
        ]);
    }




    /**
     * @OA\Post(
     *     path="/words/newWord/{wordlist_id}",
     *     summary="Add new words to a wordlist",
     *     tags={"Words"},
     *     @OA\Parameter(
     *         name="wordlist_id",
     *         in="path",
     *         description="ID of the wordlist",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON object containing the words to be added",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"words"},
     *             @OA\Property(
     *                 property="words",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"word"},
     *                     @OA\Property(
     *                         property="word",
     *                         type="string",
     *                         description="The word to be added",
     *                     ),
     *                     @OA\Property(
     *                         property="vuln",
     *                         type="boolean",
     *                         description="Whether the word is considered vulnerable or not",
     *                     ),
     *                     @OA\Property(
     *                         property="reference",
     *                         type="string",
     *                         description="Optional reference or description of the word",
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Words added to wordlist successfully",
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
     *                 example="words added to wordlist"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Bad request",
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

    public function newWord($vars, $user)
    {
        $user->hasAccess(1);

        $request_body = file_get_contents('php://input');
        try {
            $data = json_decode($request_body, true);
        }catch (\Exception $e){
            rJSON(false, 400, 'json is not valid!');
        }

        if ($data == null){
            rJSON(false, 400, 'json is not valid!');
        }

        $arr_keys = array_keys($data['words']);

        if (!isset($arr_keys[0])){
            rJSON(false, 400, 'word is not sended!');
        }

        $conn = DatabaseConnection::getInstance();

        if (!$this->wordlist_exist($vars['wl_id'])){
            rJSON(false, 400, 'wordlist not exist!');
        }

        foreach($data['words'] as $info){
            if ($this->word_exist($info['word'])){
                $word_id = $conn->get('words', 'id', ['word' => $info['word']]);
                if (!$this->word_exist_in_wordlist($word_id, $vars['wl_id'])){
                    $conn->insert('word_wordlist', [
                        'wordlist_id' => $vars['wl_id'],
                        'word_id' => $word_id
                    ]);
                }
                $this->add_point($word_id, $info['vuln']);
                $this->fix_vuln($word_id, $info['vuln']);
            } else {

                if (!isset($info['vuln'])){
                    $info['vuln'] = false;
                }

                if (!isset($info['reference'])){
                    $info['reference'] = null;
                }

                $this->addWord($info['word'], $user->id, $vars['wl_id'], $info['vuln'], $info['reference']);
            }
        }

        rJSON(true, 200, 'words added to wordlist!');


    }


    private function addWord($word, $author_id, $wordlist_id, $vuln = false, $reference = null)
    {

        $conn = DatabaseConnection::getInstance();

        $point = 0.25;

        if ($vuln){
            $point = 1;
        }

        $conn->insert('words', [
            'word' => $word,
            'points' => $point,
            'vuln' => $vuln,
            'reference' => $reference
        ]);

        $w_id = $conn->id();

        $conn->insert('word_wordlist', [
            'wordlist_id' => $wordlist_id,
            'word_id' => $w_id
        ]);

        $conn->insert('word_author', [
            'author_id' => $author_id,
            'word_id' => $w_id
        ]);

    }

    private function word_exist($word)
    {
        return DatabaseConnection::getInstance()->has('words', ['word' => $word]);
    }

    private function word_id_exist($word_id)
    {
        return DatabaseConnection::getInstance()->has('words', ['id' => $word_id]);
    }

    private function word_exist_in_wordlist($word_id, $wordlist_id)
    {
        return DatabaseConnection::getInstance()->has('word_wordlist', ['word_id' => $word_id, 'wordlist_id' => $wordlist_id]);
    }

    private function add_point($word_id,$vuln = false)
    {
        $conn = DatabaseConnection::getInstance();

        $point = 0.25;

        if ($vuln){
            $point = 1;
        }

        $conn->update('words', [
            'points[+]' => $point
        ], [
            'id' => $word_id
        ]);
    }

    private function wordlist_exist($wl_id)
    {
        return DatabaseConnection::getInstance()->has('wordlists', ['id' => $wl_id]);
    }

    private function fix_vuln($w_id,$vuln)
    {
        if ($vuln == true){
            DatabaseConnection::getInstance()->update('words', [
                'vuln' => true
            ], [
                'id' => $w_id
            ]);
        }
    }



}