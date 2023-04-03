<?php

define('HOME',__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR);

$env = parse_ini_file(HOME.'.env');
define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
define('JWT_KEY', $env['JWT_KEY']);
define('SALT', $env['SALT']);

define('TRUSTED_DOMAINS', [
    'localhost',
    'http://meow2.ir',
    'localhost:3000',
    'http://localhost:3000',
]);