<?php

use CATL\Models\User;
use CATL\Auth\Authtokens;
use CATL\Helpers\MyCookies;

$app->get('/activate[/[{hash}]]', function($request,$response,$args) use ($app)
{

    if (!$args['hash'] || strlen($args['hash']) !== 64) {
        return $response->withRedirect($this->get('router')->pathFor('login')); 
    }

    $res = User::getUserFromActiveHash($args['hash']);

    if (!$res) {
       return $response->withRedirect($this->get('router')->pathFor('login'));
    }

    $this->view->render($response, 'auth/activate.twig', [
        'activation_status'=> $res,
    ]);

})->setName('activate')->add($notauthenticated);

$app->get('/activate2', function($request,$response,$args) use ($app)
{

    // $this->view->render($response, 'templates/email/activate2.twig', [
    //     'hash'=> '1231231231231231231231231231231231231231231231231231231231231233',
    // ]);

    $this->get('flash')->addMessage('global', 'Password change successful.');
    $this->get('flash')->addMessage('global_error', 'Error here');
    
    $ms = $this->get('flash')->getMessages();

    print_r($ms);

    foreach ($ms as $key => $value) {
        foreach ($ms[$key] as $m) {
            echo $m . BR;    
        }
    }

    die();
    return $response->withRedirect($this->get('router')->pathFor('logout'));

})->setName('activate2');

