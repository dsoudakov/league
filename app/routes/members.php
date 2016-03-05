<?php

use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

$app->get('/members', function($request,$response,$args) use ($app)
{

    return $this->view->render($response, 'members/members.twig', []);

})->setName('club_members')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/leaguemembers', function($request,$response,$args) use ($app)
{

    return $this->view->render($response, 'members/league.members.twig', []);

})->setName('league_members')->add($isAdmin)->add($isMember)->add($authenticated);


$app->get('/membersjson', function($request,$response,$args) use ($app)
{

    $exp =  R::getAll( 'SELECT * FROM members' );
    //echo json_encode($exp);
	$output = ['data' => $exp];
	echo json_encode($output);    

})->setName('club_members_json')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/leaguemembersjson', function($request,$response,$args) use ($app)
{

    $exp =  R::getAll( 'SELECT 
    						   m.firstname,
    						   m.lastname,
    						   m.home,
    						   m.cell,
    						   m.work,
    						   m.email,
    						   d.divisiondesc as divisionprimary,
    						   dd.divisiondesc as divisionsecondary,
    						   u.is_admin,
    						   u.id
    						   FROM members m JOIN users u on m.email = u.email
    						   LEFT JOIN divisions d on d.id = u.divisionprimary
    						   LEFT JOIN divisions dd on dd.id = u.divisionsecondary
    						   ' );
    //echo json_encode($exp);
	$output = ['data' => $exp];
	echo json_encode($output);    

})->setName('league_members_json')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/userjson', function($request,$response,$args) use ($app)
{

    echo json_encode($app->auth->expose());

})->setName('user.json')->add($isMember)->add($authenticated);