<?php
require("../vendor/autoload.php");

$openapi = \OpenApi\Generator::scan(['../app/Controller']);
echo $openapi->toJson();