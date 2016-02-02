<?php
//define('ROOT', dirname(__DIR__).DIRECTORY_SEPARATOR);

define('ROOT', __DIR__.'/');

require_once ROOT . 'app/bootstrap.php';

$app->run();

