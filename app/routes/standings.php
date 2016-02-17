<?php

use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

$app->get('/standings', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'standings/standings.twig', []);

})->setName('standings')->add($isMember)->add($authenticated);

$app->get('/standingsjson[/{divisionid}]', function($request,$response,$args) use ($app)
{

	if (!empty($args['divisionid'])) {
		$standings = R::getAll( ' SELECT  
					concat(u.first_name, \' \', u.last_name) as player,	
					d.divisiondesc as division,
					sum(p.win) as wins,
					sum(p.loss) as losses,
					sum(p.points) as points
					FROM points p
					LEFT JOIN divisions d on d.id = p.divisionid
					LEFT JOIN users u on u.id = p.playerid
					LEFT JOIN acceptedchallenges ac on ac.id = p.acceptedchallengeid
					WHERE ac.matchtype = 1
					AND d.id = :divid
					GROUP BY player, division 
				', 
						[
							':divid' => $args['divisionid'],
						]);		
	} else {
		$standings = R::getAll( ' SELECT  
					concat(u.first_name, \' \', u.last_name) as player,	
					d.divisiondesc as division,
					sum(p.win) as wins,
					sum(p.loss) as losses,
					sum(p.points) as points
					FROM points p
					LEFT JOIN divisions d on d.id = p.divisionid
					LEFT JOIN users u on u.id = p.playerid
					LEFT JOIN acceptedchallenges ac on ac.id = p.acceptedchallengeid
					WHERE ac.matchtype = 1
					GROUP BY player, division 
				');				
	}

	

	echo json_encode($standings);		

})->setName('standings.get.json')
  ->add($isMember)
  ->add($authenticated);
