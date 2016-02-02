<?php

use CATL\Models\User;

$app->get('/password-recover', function($request,$response,$args) use ($app)
{
    $this->view->render($response, 'auth/password-recover.twig', []);

})->setName('password.recover')->add($notauthenticated)->add(new GenCsrf);

$app->post('/password-recover', function($request,$response,$args) use ($app)
{

    $identifier = $request->getParam('identifier'); 
    $_SESSION['loginemail'] = $identifier;
    $v = $this->get('validator');

    $v->validate([
        'identifier|Email' => [$identifier, 'required|email'],
    ]);

    if ($v->passes()) {

        $user = new User($identifier);

        if ($user->exists) {
    
            $recover_id = $this->get('randomlib')->generateString(128);
            $recover_hash = $this->get('hash')->hash($recover_id);

            $user->user->recover_hash = $recover_hash;
            $id = $user::storeBean($user->user);

            if ($id) {
                try {
                    $mres = $this->get('mail')->sendMessage($this->get('config')->get('services.mailgun.domain') ,[
                        'from' => 'Tennis League <postmaster@catennisleague.com>',
                        'to' => $user->user->email,
                        'subject' => 'Password recovery.',
                        'html' => $this->get('view')->fetch('templates/email/password-reset.twig', [
                            'recover_id' => $recover_id,
                            'email' => $user->user->email,
                        ])
                    ]);
                    
                } catch (Exception $e) {

                    $this->get('flash')->addMessage('global_error', 'Failed to send recover email: ' . $e->getMessage());
                    $response = $response->withRedirect($this->get('router')->pathFor('password.recover'));
                    return $response;
                }
            
                $this->get('flash')->addMessage('global', 'Email sent.');
                $response = $response->withRedirect($this->get('router')->pathFor('password.recover'));
                return $response;
            } else {

                $this->get('flash')->addMessage('global_error', 'Database error. Please retry.');
                $response = $response->withRedirect($this->get('router')->pathFor('password.recover'));
                return $response;

            }

        } else {
                $_SESSION['loginemail'] = null;
                $this->get('flash')->addMessage('global_error', 'No such email.');
                $response = $response->withRedirect($this->get('router')->pathFor('password.recover'));
                return $response;

        }

    } else {

            $this->get('flash')->addMessage('global_error', 'Input errors. Please check.');
            $response = $response->withRedirect($this->get('router')->pathFor('password.recover'));
            return $response;

    }


})->setName('password.recover.post')->add($notauthenticated);


$app->get('/password-reset', function($request,$response,$args) use ($app)
{
    $email = $request->getParam('email');
    $recover_id = $request->getParam('recover_id');

    $v = $this->get('validator');

    $v->validate([
        'email|Email' => [$email, 'required|email'],
        'recover_id' => [$recover_id, 'required|max(128)'],
    ]);

    if ($v->passes()) {
        
        $user = new User($email);

        if (!$user->exists) {
            $this->get('flash')->addMessage('global_error', 'User does not exist. Contanct administrator.');
            return $response->withRedirect($this->get('router')->pathFor('login'));
        }

        if (!$user->user->recover_hash) {
            $this->get('flash')->addMessage('global_error', 'Password reset is not requested.');
            return $response->withRedirect($this->get('router')->pathFor('home'));
        }

        $recover_hash = $this->get('hash')->hash($recover_id);

        if (!$this->get('hash')->hashCheck($user->user->recover_hash, $recover_hash)) {
            $this->get('flash')->addMessage('global_error', 'Did you request password reset more than once? Click the button in the last email.');
            return $response->withRedirect($this->get('router')->pathFor('login'));
        }

        return $this->view->render($response, 'auth/password-reset.twig', [
            'email' => $user->user->email,
            'recover_id' => $recover_id
        ]);
    }else {
        $this->get('flash')->addMessage('global_error', 'Password reset FAILED.');
        return $response->withRedirect($this->get('router')->pathFor('login'));
    }

})->setName('password.reset')->add($notauthenticated)->add(new GenCsrf);

$app->post('/password-reset', function($request,$response,$args) use ($app)
{

    $email = $request->getParam('email');
    $recover_id = $request->getParam('recover_id');

    $password = $request->getParam('password');
    $password_match = $request->getParam('password_match');

    $v = $this->get('validator');

    $v->validate([
        'email|Email' => [$email, 'required|email'],
        'password' => [$password, 'required|min(6)'],
        'password_match' => [$password_match, 'required|matches(password)'],
    ]);


    if ($v->passes()) {
        
        $user = new User($email);

        if (!$user->exists) {
            $this->get('flash')->addMessage('global_error', 'User does not exist. Contact administrator.');
            return $response->withRedirect($this->get('router')->pathFor('login'));
        }

        if (!$user->user->recover_hash) {
            $this->get('flash')->addMessage('global_error', 'Password reset is not requested.');
            return $response->withRedirect($this->get('router')->pathFor('home'));
        }

        $recover_hash = $this->get('hash')->hash($recover_id);

        if (!$this->get('hash')->hashCheck($user->user->recover_hash, $recover_hash)) {
            $this->get('flash')->addMessage('global_error', 'Did you request password reset more than once? Click the button in the last email.');
            return $response->withRedirect($this->get('router')->pathFor('login'));
        }

        $ret = $user->update([
            'password' => $this->get('hash')->password($password),
            'recover_hash' => null,
        ]);

        if ($ret) {
            $this->get('flash')->addMessage('global', 'Password was reset succesfully.');
            return $response->withRedirect($this->get('router')->pathFor('login'));
        } else {
            $this->get('flash')->addMessage('global_error', 'Password reset FAILED.');
            return $response->withRedirect($this->get('router')->pathFor('password.reset'));
        }

    } else {
        $this->get('flash')->addMessage('global_error', 'Password reset FAILED.');
        return $response->withRedirect($this->get('router')->pathFor('password.reset', [
            'email' => $email,
            'recover_id' => $recover_id,
        ]));
    }

})->setName('password.reset.post')->add($notauthenticated);

$app->get('/password-change', function($request,$response,$args) use ($app)
{
    $this->view->render($response, 'auth/password-change.twig', []);

})->setName('password.change')->add($authenticated)->add(new GenCsrf);

$app->post('/password-change', function($request,$response,$args) use ($app)
{

    $current_password = $request->getParam('current_password');
    $password = $request->getParam('password');
    $password_match = $request->getParam('password_match');

    $v = $this->get('validator');

    $v->validate([
        'current_password|Current password' => [$current_password, 'required|min(6)|max(50)'],
        'password' => [$password, 'required|min(6)|max(50)'],
        'password_match' => [$password_match, 'required|matches(password)'],
    ]);

    if ($v->passes()) {

        if ($app->auth->checkPassword($current_password)) {
            
            $app->user->password = $this->get('hash')->password($password);
            $ret = $app->auth->storeBean($app->auth->user);

            if ($ret) {

                try {
                    $mres = $this->get('mail')->sendMessage($this->get('config')->get('services.mailgun.domain') ,[
                        'from' => 'Tennis League <postmaster@catennisleague.com>',
                        'to' => $app->user->email,
                        'subject' => 'Password changed.',
                        'html' => $this->get('view')->fetch('templates/email/password-change.twig', []),
                    ]);
                    
                } catch (Exception $e) {

                    $this->get('flash')->addMessage('global_error', 'Password change successful, but failed to send confirmation email: ' . $e->getMessage());
                    $response = $response->withRedirect($this->get('router')->pathFor('password.change'));
                    return $response;
                }

                $this->get('flash')->addMessage('global', 'Password change successful.');
                $this->get('flash')->addMessage('global', 'Please log in again.');
                return $response->withRedirect($this->get('router')->pathFor('logout'));
              
            } else {

                $this->get('flash')->addMessage('global_error', 'Password change FAILED.');
                return $response->withRedirect($this->get('router')->pathFor('home'));

            }
        } else {

                $this->get('flash')->addMessage('global_error', 'Password change FAILED. Current password is incorrect.');
                return $response->withRedirect($this->get('router')->pathFor('password.change'));
        }
    
    } else {

        $this->get('flash')->addMessage('global_error', 'Password change FAILED.');
        return $response->withRedirect($this->get('router')->pathFor('password.change'));
    }

})->setName('password.change.post')->add($authenticated);