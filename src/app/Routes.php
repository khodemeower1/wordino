<?php


$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {


    $r->addGroup('/authors', function (\FastRoute\RouteCollector $r) {
        $r->post('/login', 'AuthorController@login');
        $r->post('/changePassword', 'AuthorController@changePassword');
        $r->post('/newAuthor', 'AuthorController@newAuthor');
        $r->get('/list', 'AuthorController@list');
        $r->get('/info/{id:\d+}', 'AuthorController@info');
        $r->post('/changeAccessLevel', 'AuthorController@changeAccessLevel');
        $r->post('/removeUser', 'AuthorController@removeUser');

    });

    $r->addGroup('/wordlists', function (\FastRoute\RouteCollector $r) {
        $r->post('/newWordlist', 'WordlistController@newWordlist');
        $r->get('/getWordlist/{id:\d+}', 'WordlistController@getWordlist');
        $r->get('/getWordlistFile/{id:\d+}', 'WordlistController@getWordlistFile');
        $r->get('/info/{id:\d+}', 'WordlistController@info');
        $r->post('/addWordlistToGroup', 'WordlistController@addWordlistToGroup');
        $r->post('/removeWordlistFromGroup', 'WordlistController@removeWordlistFromGroup');
        $r->get('/list', 'WordlistController@list');
        $r->delete('/delete/{id:\d+}', 'WordlistController@delete');
        $r->post('/rename', 'WordlistController@rename');
    });

    $r->addGroup('/groups', function (\FastRoute\RouteCollector $r) {
        $r->post('/newGroup', 'GroupController@newGroup');
        $r->get('/removeGroup/{id:\d+}', 'GroupController@removeGroup');
        $r->get('/getWords/{id:\d+}', 'GroupController@getWords');
        $r->get('/list', 'GroupController@list');
        $r->get('/info/{id:\d+}', 'GroupController@info');
    });

    $r->addGroup('/words', function (\FastRoute\RouteCollector $r) {
        $r->post('/newWord/{wl_id:\d+}', 'WordController@newWord');
        $r->post('/delete', 'WordController@delete');
    });

});

