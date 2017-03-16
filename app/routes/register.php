<?php

use CATL\Models\User;
use CATL\Auth\Authtokens;
use CATL\Helpers\MyCookies;

$app->get('/register', function($request,$response,$args) use ($app)
{

    if (isset($_SESSION['v'])) {
        $app->v = unserialize($_SESSION['v']);    
    }
  
    $this->view->render($response, 'auth/register.twig', [

    ]);

})->setName('register')->add($notauthenticated)->add(new GenCsrf);

$app->post('/register', function($request,$response,$args) use ($app) 
{
    $recaptcha = new \ReCaptcha\ReCaptcha('6Lf1TRkUAAAAAEvhE3LmrGnsaFJGfN9BGP9eds19');
  
    $gRecaptchaResponse = $request->getParam('g-recaptcha-response');
    $resp = $recaptcha->verify($gRecaptchaResponse, null);
    $resp = $resp->isSuccess();

    $identifier = $request->getParam('identifier');
    $_SESSION['loginemail'] = $identifier;
    $password = $request->getParam('password');
    $password_match = $request->getParam('password_match');
    //$remember = $request->getParam('remember');
    
    $v = $this->get('validator');

    $v->validate([
        'identifier|Email' => [$identifier, 'required|email|unique(users,email)'],
        'password|Password' => [$password, 'required|min(6)|max(20)'],
        'password_match|Password' => [$password_match, 'required|matches(password)'],
        'g-recaptcha-response|reCAPTCHA' => [$resp, 'true'],
    ]);

    if ($v->passes()) {

        $_SESSION['v'] = false;
        $user = User::create($identifier,$password);

        if ($user->exists) {
                
                $_SESSION['v'] = false;

                try {
                    $mres = $this->get('mail')->sendMessage($this->get('config')->get('services.mailgun.domain') ,[
                        'from' => 'Tennis League <postmaster@catennisleague.com>',
                        'to' => $user->user->email,
                        'subject' => 'Thank you for registering!',
                        'html' => $this->get('view')->fetch('templates/email/activate.twig', [
                            'hash' => $user->user->active_hash,
                        ])
                    ]);
                    
                } catch (Exception $e) {

                    $this->get('flash')->addMessage('global_error', 'Failed to register: ' . $e->getMessage());
                    $response = $response->withRedirect($this->get('router')->pathFor('login'));
                    $user::deleteBean($user);
                    return $response;
                }

                $this->get('flash')->addMessage('global', 'You have registered! Check your email and activate your account before logging in!');
                $response = $response->withRedirect($this->get('router')->pathFor('login'));
                return $response;
        } else {
            
            $_SESSION['v'] = false; 
            $this->get('flash')->addMessage('global_error', 'Failed to create user! Are you a member of the league? Please use the same email address. Please try again.');
            $response = $response->withRedirect($this->get('router')->pathFor('register'));
            return $response;

        }
    } else {

        $_SESSION['v'] = serialize($v);
        $this->get('flash')->addMessage('global_error', 'Registration failed.');    
        $response = $response->withRedirect($this->get('router')->pathFor('register'));
        return $response;
    }

})->setName('register.post')->add($notauthenticated);

