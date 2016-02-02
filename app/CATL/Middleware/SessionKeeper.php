<?php

namespace CATL\Middleware;

class SessionKeeper {
	public function checkActivity()
	{
		//echo time() - $_SESSION['CREATED'] . BR;
		//echo session_status() . PHP_SESSION_ACTIVE . BR;
		if (!isset($_SESSION['CREATED'])) {
		    $_SESSION['CREATED'] = time();
		} elseif (time() - $_SESSION['CREATED'] > 40) {
		    if (session_status() === PHP_SESSION_ACTIVE) {
			    //session_regenerate_id(true);
			    $_SESSION['CREATED'] = time();
		    }
		}
	}

    public function __invoke($request, $response, $next)
    {
    	$this->checkActivity();

        $response = $next($request, $response);
        return $response;
    }	
}