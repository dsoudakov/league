<?php
use CATL\Models\User;
use CATL\Auth\Authtokens;
use CATL\Helpers\MyCookies;

$app->get('/logout', function($request,$response,$args) use ($app) {
	
	unset($_SESSION[$this->get('config')->get('auth.session')]);
	unset($_SESSION['loginemail']);
	
	$cookie = new MyCookies($request,  $response);

	$cookieValue = $cookie->get($this->get('config')->get('auth.remember'));

	if ($cookieValue) {
		$credentials = explode('___', $cookieValue);	
		
		if (trim($data) == true || count($credentials) == 2) {	
			
			$res = Authtokens::deleteToken($credentials[0]);
			if ($res) {
				$response = $cookie->delete($this->get('config')->get('auth.remember'));	
			}
		}
	};


    $ms = $this->get('flash')->getMessages();

    if ($ms) {
	    foreach ($ms as $k => $v) {
	        foreach ($ms[$k] as $m) {
				$this->get('flash')->addMessage($k, $m);
	        }
	    }	
	}

	$this->get('flash')->addMessage('global', 'You have been logged out.');

	$response = $response->withRedirect($this->get('router')->pathFor('home'));
	return $response;

})->setName('logout')->add($authenticated);