<?php


$app->get('/divisions', function($request,$response,$args) use ($app)
{
	return $this->view->render($response, 'division/divisions.twig', []);

})->setName('divisions')->add($authenticated)->add(new GenCsrf);


$app->post('/divisions', function($request,$response,$args) use ($app)
{
	

})->setName('divisions.post')->add($authenticated)->add(new GenCsrf);