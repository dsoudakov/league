<?php

use CATL\R;

$app->get('/players', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'players.twig', []);

})->setName('players')->add($isMember)->add($authenticated)->add(new GenCsrf);


$app->get('/playersgetjson', function($request,$response,$args) use ($app)
{

	 $players = R::getAll( 'SELECT  firstname, 
	 								lastname, 
	 								home, 
	 								work, 
	 								cell, 
	 								u.email, 
	 								division1, 
	 								division2 
	 						FROM users u 
	 						JOIN members m ON u.email = m.email 
	 						WHERE u.active = 1' );

	 echo json_encode($players);
 
})->setName('players.get.json')->add($isMember)->add($isAdmin)->add($authenticated)->add(new GenCsrf);
