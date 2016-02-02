<?php

use CATL\Models\User;
use CATL\Auth\Authtokens;
use CATL\Helpers\MyCookies;

$app->get('/login', function($request,$response,$args) use ($app)
{

    $this->view->render($response, 'auth/login.twig', [

    ]);

})->setName('login')->add($notauthenticated)->add(new GenCsrf);

$app->post('/login', function($request,$response,$args) use ($app) 
{
   
    $identifier = $request->getParam('identifier');
    $_SESSION['loginemail'] = $identifier;
    $password = $request->getParam('password');
    $remember = $request->getParam('remember');
    
    if ($remember == "on") {
    	$_SESSION['checked'] = "checked";
    }

    $v = $this->get('validator');

    $v->validate([
        'identifier|Email' => [$identifier, 'required'],
        'password' => [$password, 'required|max(20)']
    ]);

    if ($v->passes()) {

        $user = new User($identifier);

        if ($user->exists) {
            
            if ($user->checkPassword($password)) {
            	if ($user->user->active) {
                
	                //set var for templates
	                $this->auth = $user;
	                $this->user = $user->user;

	                // user authenticated ok
	                // set session var for user's id in db
	                $_SESSION[$this->get('config')->get('auth.session')] = $user->id;

	                $this->get('view')->offsetSet('auth', $user);
	                $this->get('view')->offsetSet('user', $user->user);

	                if ($remember === 'on') {
	                 
	                    //pull up users tokens, delete old ones
	                    $authtoken = new Authtokens($user->id);

	                	$atoken = $authtoken->addToken($user->id);
	                	$rememberIdentifier = $atoken['remember_identifier'];
	                	$rememberToken = $atoken['remember_token'];

	                	//create cookie

	                	$cookie = new MyCookies($request,$response);
	                	$response = $cookie->set(
	                			$this->get('config')->get('auth.remember'),			//cookie name
								"{$rememberIdentifier}___{$rememberToken}", //cookie value
								$this->get('config')->get('auth.cookieexpire')  	// +1 week
	                		);
	                } 

	                $this->get('flash')->addMessage('global', 'You logged in! Welcome!');
	                $response = $response->withRedirect($this->get('router')->pathFor('home'));
	            } else {
	            	$this->get('flash')->addMessage('global_error', 'Your account is not activated yet.');
	           		$response = $response->withRedirect($this->get('router')->pathFor('login'));
	            }
            } else {
            	$this->get('flash')->addMessage('global_error', 'Username or password is incorrect.');
            	$response = $response->withRedirect($this->get('router')->pathFor('login'));
            }
        }
    } else {

       	$this->get('flash')->addMessage('global_error', 'Username or password is incorrect.');
       	$response = $response->withRedirect($this->get('router')->pathFor('login'));

    }

	return $response;

})->setName('login.post')->add($notauthenticated);

