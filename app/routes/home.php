<?php

$app->get('/', function ($request,$response,$args) use ($app)
{
	if ($app->auth->isMember()) {
		$response = $this->view->render($response, 'challenge/challenges.issued.twig', []);
	} else {
		$response = $this->view->render($response, 'home.twig', []);
	}

	return $response;

})->setName('home')->add($authenticated);