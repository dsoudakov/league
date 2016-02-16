<?php

use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

$app->get('/members', function($request,$response,$args) use ($app)
{

    return $this->view->render($response, 'angular/club.members.twig', []);

})->setName('club_members')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/membersjson', function($request,$response,$args) use ($app)
{

    $exp =  R::getAll( 'SELECT * FROM members' );
    echo json_encode($exp);

})->setName('club_members_json')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/userjson', function($request,$response,$args) use ($app)
{

    echo json_encode($app->auth->expose());

})->setName('user.json')->add($isMember)->add($authenticated);

