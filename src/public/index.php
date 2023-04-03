<?php

require '../vendor/autoload.php';
require_once '../system/boot.php';


require_once HOME.'app/Routes.php';

use System\User;


$controller_namespace = '\\App\\Controller\\';
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        rJSON(false, 404, 'page not found!');
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            cors($allowedMethods);
            rJSON(true, 200, 'ok');
        }
        rJSON(false, 405, 'method not allowed!');
        break;
    case FastRoute\Dispatcher::FOUND:
        cors();
        $handler = explode('@', $routeInfo[1]);
        $handler_class = $handler[0];
        $handler_method = $handler[1];
        $vars = $routeInfo[2];
        $full_class_with_nameSpace = $controller_namespace . $handler_class;
        if ($handler_method != 'login') {
            $headers = getallheaders();
            if (!isset($headers['Authorization'])) {
                $headers['Authorization'] = 'none';
            }else{
                $headers['Authorization'] = str_replace('Bearer ', '', $headers['Authorization']);
            }
            $user = User::isAuthenticated($headers['Authorization']);
            $class = new $full_class_with_nameSpace();
            $class->$handler_method($vars, $user);
        } else {
            $class = new $full_class_with_nameSpace();
            $class->$handler_method($vars);
        }
}