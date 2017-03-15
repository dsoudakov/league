<?php
define('ROOT', __DIR__.DIRECTORY_SEPARATOR);

echo ROOT;

require_once ROOT . 'app/bootstrap.php';

$app->run();