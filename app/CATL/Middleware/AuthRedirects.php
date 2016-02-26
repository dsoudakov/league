<?php

global $app;

$isMember = function ($request, $response, $next) use ($app) {

    if ($app->auth) {
        if ($app->auth->isMember()) {
            $response = $next($request, $response);    
        } else {
            $app->getContainer()->get('flash')->addMessage('global_error', 'You need to be a member to do this. Please join the club and subscribe for the league.');
            $response = $response->withRedirect($this->get('router')->pathFor('home'));
        }
       
    } else {
        $response = $response->withRedirect($this->get('router')->pathFor('login'));
    }

    return $response;
};

$isAdmin = function ($request, $response, $next) use ($app) {

    if ($app->auth) {
        if ($app->auth->isAdmin()) {
            $response = $next($request, $response);    
        } else {
            $response = $response->withRedirect($this->get('router')->pathFor('home'));
        }
       
    } else {
        $response = $response->withRedirect($this->get('router')->pathFor('login'));
    }

    return $response;
};

$notauthenticated = function ($request, $response, $next) use ($app) {

    if (!$app->auth) {
        $response = $next($request, $response);  
    } else {
        $response = $response->withRedirect($this->get('router')->pathFor('home'));
    }

    return $response;
};

$authenticated = function ($request, $response, $next) use ($app) {

    //dump($app);
    if ($app->auth->exists) {
        $response = $next($request, $response);  
    } else {
        $response = $response->withRedirect($this->get('router')->pathFor('login'));
    }

    return $response;
};

class GenCsrf
{
    public function __invoke($request, $response, $next) 
    {
    	global $app;

        $app->csrf_name  = $request->getAttribute($app->getContainer()->get('csrf')->getTokenNameKey());
        $app->csrf_value = $request->getAttribute($app->getContainer()->get('csrf')->getTokenValueKey());

        //$app->getContainer()->get('flash')->addMessage('global', 'here!');

        $response = $next($request, $response);

        return $response;
    }
}

class mw2
{
    public function __invoke($request, $response, $next)
    {
    	global $app;
    	$cookie = $app->cookie;

    	// $cookie = new MyCookies;
    	// $cookie->up($request, $response);

    	//$cookie->set('test7', 'value7', '+1 week');
        //$cookie->delete('test4');
        $response = $next($cookie->req(), $cookie->res());

        return $response;
    }
}