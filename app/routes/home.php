<?php

$app->get('/', function ($request,$response,$args) use ($app)
{
	
	$response = $this->view->render($response, 'home.twig', []);

	return $response;

})->setName('home');


$app->get('/about', function ($request,$response,$args) use ($app)
{
	
	$response = $this->view->render($response, 'about.twig', []);

	return $response;

})->setName('about');


$app->get('/aboutclub', function ($request,$response,$args) use ($app)
{
	
	$response = $this->view->render($response, 'aboutclub.twig', []);

	return $response;

})->setName('about.club');