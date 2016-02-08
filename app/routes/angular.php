<?php

use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

$app->get('/angular', function($request,$response,$args) use ($app)
{

    return $this->view->render($response, 'angular/angular.twig', []);

})->setName('angular1')->add($isMember)->add($authenticated);

$app->get('/angularjson', function($request,$response,$args) use ($app)
{

    $exp =  R::getAll( 'SELECT * FROM members' );
    echo json_encode($exp);

})->setName('angular2')->add($isMember)->add($authenticated);

$app->get('/userjson', function($request,$response,$args) use ($app)
{

    echo json_encode($app->auth->expose());

})->setName('user.json')->add($isMember)->add($authenticated);

