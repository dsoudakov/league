<?php

use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

$app->get('/members', function($request,$response,$args) use ($app)
{

    return $this->view->render($response, 'members/members.twig', []);

})->setName('club_members')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/members2', function($request,$response,$args) use ($app)
{

    return $this->view->render($response, 'members/club.members.twig', []);

})->setName('club_members2')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/membersjson', function($request,$response,$args) use ($app)
{

    $exp =  R::getAll( 'SELECT * FROM members' );
	echo json_encode($exp);

})->setName('club_members_json')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/membersjson2', function($request,$response,$args) use ($app)
{

    $exp =  R::getAll( 'SELECT * FROM members' );
    //echo json_encode($exp);
	$output = ['data' => $exp];
	echo json_encode($output);    

})->setName('club_members_json2')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/userjson', function($request,$response,$args) use ($app)
{

    echo json_encode($app->auth->expose());

})->setName('user.json')->add($isMember)->add($authenticated);

