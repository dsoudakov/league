<?php
namespace CATL\Middleware;

use CATL\Models\User;
use CATL\Helpers\MyCookies;
use CATL\Auth\Authtokens;

class Authcheck
{

    protected $app;
    protected $c;
    protected $cookie;
    protected $cookieName;
    protected $request;
    protected $response;

    function __invoke($request,  $response, $next) {

//        die('running Authcheck');
        usleep(rand(10,rand(20,50)));
    	global $c, $app;

        if (false === $request->getAttribute('csrf_status')) {
            //die('csrf');
            $app->getContainer()->get('flash')->addMessage('global_error', 'Error!');
            return $response->withRedirect($app->getContainer()->get('router')->pathFor('login'));
        } 
        
        $this->app = $app;
        $this->c = $c;

        //dump($app->get('config')->get('auth.remember'));
        //die();


        $this->cookieName = $this->c->config->get('auth.remember');

    	$this->request = $request;
    	$this->response = $response;

        $this->run(); //check session, generate user object if id is in the session

        //dump($app);
        //die('Authcheck complete');
        //dump($this->c->get('view'));

        $newResponse = $next($this->request, $this->response);
        
        return $newResponse;
    }

    public function run()
    {
        if (isset($_SESSION[$this->c->config->get('auth.session')])) {
            
            $id = $_SESSION[$this->c->config->get('auth.session')];
            
            $user = new \CATL\Models\User($id);


            if ($user) {

                $this->app->auth = $user;
                $this->app->user = $user->user;
                $this->c->get('view')->offsetSet('auth', $user);
                $this->c->get('view')->offsetSet('user', $user->user);

            } else {

                unset($_SESSION[$this->c->config->get('auth.session')]);
            	$this->response = $this->$response->withRedirect($this->c->get('router')->pathFor('login'));

            }
        } else {

        	$this->checkRememberMe();  // no id in session, check cookie

        }
    }

    protected function checkRememberMe()
    {
    	$this->cookie = new MyCookies($this->request,  $this->response);

        if ($this->cookie->get($this->cookieName) && !$this->app->user) {

            $data = $this->cookie->get($this->cookieName);
            $credentials = explode('___', $data);

            if (trim($data) == false || count($credentials) !== 2) {
            	
                $this->response = $this->cookie->delete($this->cookieName);
                $this->response = $this->response->withRedirect($this->c->get('router')->pathFor('login'));
            
            } else {

                $identifier = $credentials[0];
                $tokenhash = $this->c->hash->hash($credentials[1]);

                //check db for token exists and not expired
                $authtoken = Authtokens::getHashIdFromToken($identifier);


                if ($authtoken) {
                    if ($this->c->hash->hashCheck($tokenhash, $authtoken['hash'])) {

                           $user = new User($authtoken['user_id']);
                            
                            if ($user->exists){

                                $_SESSION[$this->c->config->get('auth.session')] = $user->id;

				                $this->app->auth = $user;
                                $this->app->user = $user->user;

                                $this->c->get('view')->offsetSet('auth', $user);
				                $this->c->get('view')->offsetSet('user', $user->user);

                                $this->c->get('flash')->addMessage('global', 'Welcome back!');

                                $this->response = $this->response->withRedirect($this->c->get('router')->pathFor('home'));
                            }
                    } else { // hash check failed, bad data

                        $this->response = $this->cookie->delete($this->cookieName);
                        $this->response = $this->response->withRedirect($this->c->get('router')->pathFor('login'));
                    
                    } 

                } else { // expired or non-existent token

                    $this->response = $this->cookie->delete($this->cookieName);
                    $this->response = $this->response->withRedirect($this->c->get('router')->pathFor('login'));
                }
            }
        } 
    }
}