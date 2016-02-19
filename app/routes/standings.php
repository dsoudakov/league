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
					sum(p.win) + sum(p.loss) as matches,
					sum(p.points) as points,
					u.id as userid,
					d.id as divisionid
					FROM points p
					LEFT JOIN divisions d on d.id = p.divisionid
					LEFT JOIN users u on u.id = p.playerid
					LEFT JOIN acceptedchallenges ac on ac.id = p.acceptedchallengeid
					WHERE ac.matchtype = 1 AND ac.reportconfirmed = 1
					AND d.id = :divid
					GROUP BY player, division , u.id, d.id
				', 
						[
							':divid' => $args['divisionid'],
						]);		
	} else {
		$standings = R::getAll( ' SELECT  
					concat(u.first_name, \' \', u.last_name) as player,	
					d.divisiondesc as division,
					sum(p.win) + sum(p.loss) as matches,
					sum(p.win) as wins,
					sum(p.loss) as losses,
					sum(p.points) as points,
					u.id as userid,
					d.id as divisionid
					FROM points p
					LEFT JOIN divisions d on d.id = p.divisionid
					LEFT JOIN users u on u.id = p.playerid
					LEFT JOIN acceptedchallenges ac on ac.id = p.acceptedchallengeid
					WHERE ac.matchtype = 1 AND ac.reportconfirmed = 1
					GROUP BY player, division, u.id, d.id
				');				
	}

	$output = ['data' => $standings];

	echo json_encode($output);

})->setName('standings.get.json')
  ->add($isMember)
  ->add($authenticated);

$app->get('/playerdetailsjson/{userid}', function($request,$response,$args) use ($app)
{

	$playerdetails = R::getAll( ' SELECT  

					concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate,
					IF(c.challengerid = :uid,
						concat(uu.first_name, \' \', uu.last_name),
						concat(uuu.first_name, \' \', uuu.last_name)
					) as vsplayer,
					d.divisiondesc as division,
					IF(p.win = 1, \'W\',\'L\') as result,
					p.points
					
					FROM points p
					LEFT JOIN divisions d on d.id = p.divisionid
					LEFT JOIN users u on u.id = p.playerid
					LEFT JOIN acceptedchallenges ac on ac.id = p.acceptedchallengeid
					LEFT JOIN users uu on uu.id = ac.acceptedbyuserid
					LEFT JOIN challenges c on c.id = ac.acceptedchallengeid
					LEFT JOIN users uuu on uuu.id = c.challengerid
					WHERE ac.matchtype = 1 AND ac.reportconfirmed = 1
					AND u.id = :uid
					-- GROUP BY player, division , u.id, d.id
				', 
				[
					':uid' => $args['userid'],
				]);	

	$output = ['data' => $playerdetails];
	echo json_encode($output);

})->setName('playerdetails.get.json')
  ->add($isMember)
  ->add($authenticated);