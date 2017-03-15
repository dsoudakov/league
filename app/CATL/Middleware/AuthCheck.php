<?php
namespace CATL\Middleware;

use CATL\Models\User;
use CATL\Helpers\MyCookies;
use CATL\Auth\Authtokens;

class Authcheck
{

    protected $cookie;
    protected $cookieName;
    protected $request;
    protected $response;

    function __invoke($request,  $response, $next) {

        global $app, $c;
        usleep(rand(10,rand(20,50)));

        if (false === $request->getAttribute('csrf_status')) {
            $app->getContainer()->get('flash')->addMessage('global_error', 'Error!');
            return $response->withRedirect($app->getContainer()->get('router')->pathFor('login'));
        } 

        $this->cookieName = $c->config->get('auth.remember');
    	$this->request = $request;
    	$this->response = $response;

        $this->run(); //check session, generate user object if id is in the session

        $newResponse = $next($this->request, $this->response);
        
        return $newResponse;
    }

    public function run()
    {
        global $app, $c;

        if (isset($_SESSION[$c->config->get('auth.session')])) {
            
            $id = $_SESSION[$c->config->get('auth.session')];
            
            $user = new \CATL\Models\User($id);

            if ($user->exists) {

                $app->auth = $user;
                $app->user = $user->user;
                $c->get('view')->offsetSet('auth', $user);
                $c->get('view')->offsetSet('user', $user->user);

            } else {

                unset($_SESSION[$c->config->get('auth.session')]);
            	$this->response = $this->response->withRedirect($c->get('router')->pathFor('login'));

            }
        } else {
        	$this->checkRememberMe();  // no id in session, check cookie
        }
    }

    protected function checkRememberMe()
    {
        global $app, $c;
    	$this->cookie = new MyCookies($this->request,  $this->response);
        
        if ($this->cookie->get($this->cookieName) && !$app->user) {

            $data = $this->cookie->get($this->cookieName);
            $credentials = explode('___', $data);

            if (trim($data) == false || count($credentials) !== 2) {
            	
                $this->response = $this->cookie->delete($this->cookieName);
                $this->response = $this->response->withRedirect($c->get('router')->pathFor('login'));
            
            } else {

                $identifier = $credentials[0];
                $tokenhash = $c->hash->hash($credentials[1]);

                //check db for token exists and not expired
                $authtoken = Authtokens::getHashIdFromToken($identifier);

                if ($authtoken) {
                    if ($c->hash->hashCheck($tokenhash, $authtoken['hash'])) {

                           $user = new User($authtoken['user_id']);
                            
                            if ($user->exists){

                                $_SESSION[$c->config->get('auth.session')] = $user->id;

				                $app->auth = $user;
                                $app->user = $user->user;

                                $c->get('view')->offsetSet('auth', $user);
				                $c->get('view')->offsetSet('user', $user->user);

                                $c->get('flash')->addMessage('global', 'Welcome back!');

                                // $this->response = $this->response->withRedirect($c->get('router')->pathFor('home'));
                                $this->response = $this->response->withRedirect($_SERVER['REQUEST_URI']);

                            } else { // user deleted

                                $this->response = $this->cookie->delete($this->cookieName);
                                $this->response = $this->response->withRedirect($c->get('router')->pathFor('login'));

                            }
                    } else { // hash check failed, bad data

                        $this->response = $this->cookie->delete($this->cookieName);
                        $this->response = $this->response->withRedirect($c->get('router')->pathFor('login'));
                    
                    } 

                } else { // expired or non-existent token

                    $this->response = $this->cookie->delete($this->cookieName);
                    $this->response = $this->response->withRedirect($c->get('router')->pathFor('login'));
                }
            }
        } 
    }
}