<?php

$app->get('/togglemode', function ($request,$response,$args) use ($app)
{

	$file = ROOT . 'config/mode.php';
	$current = file_get_contents($file);

	if ($current == '_prod') {
		
		file_put_contents($file, '_dev');

	} else {

		file_put_contents($file, '_prod');

	}

	return $response->withRedirect($this->get('router')->pathFor('login'));

})->setName('togglemode');