<?php

$app->get('/', function ($request,$response,$args) use ($app)
{

	if ($app->auth->isMember()) {
		echo 'Hello Member!';
	} else {
		echo 'Not a member, please join the club!';
	}

	$response = $this->view->render($response, 'home2.twig', [
	]);
	return $response;

})->setName('home')->add($authenticated);