<?php

use CATL\Models\User;

$app->get('/profile', function($request,$response,$args) use ($app)
{
	return $this->view->render($response, 'profile/profile.twig', []);

})->setName('profile')->add($authenticated)->add(new GenCsrf);


$app->post('/profile', function($request,$response,$args) use ($app)
{

    $first_name = $request->getParam('first_name');
    $last_name = $request->getParam('last_name');
    $sex = $request->getParam('sex');
    $hand = $request->getParam('hand');
    $skill7 = $request->getParam('skill7');
    $skillOTA = $request->getParam('skillOTA');
    $donotnotifyme = $request->getParam('donotnotifyme');
    $divisionprimary = $request->getParam('divisionprimary');
    $divisionsecondary = $request->getParam('divisionsecondary');
    
    $v = $this->get('validator');

    $v->validate([
        'first_name|First name' => [$first_name, 'max(30)'],
        'last_name|Last name' => [$last_name, 'max(30)'],
        'sex|Sex' => [$sex, 'required|max(15)'],
        'hand|Hand' => [$hand, 'required|max(15)'],
        'skill7|Skill 7.0' => [$skill7, 'required|max(15)'],
        'donotnotifyme|On vacation' => [$donotnotifyme, 'between(0,1)'],
        'divisionprimary|Primary division' => [$divisionprimary, 'between(0,99)'],
        'divisionsecondary|Secondary division' => [$divisionsecondary, 'between(0,99)'],
    ]);

    if ($v->fails()) {

		return $this->view->render($response, 'profile/profile.twig', [
			'errors' => $v->errors(),
			'request' => $request,
		]);
    }

    $app->auth->user->first_name = $first_name;
    $app->auth->user->last_name = $last_name;
    $app->auth->user->sex = $sex;
    $app->auth->user->hand = $hand;
    $app->auth->user->skill7 = $skill7;
    $app->auth->user->skillOTA = $skillOTA;
    $app->auth->user->donotnotifyme = $donotnotifyme;

    if ($divisionprimary !== $app->auth->user->divisionprimary) {
        if ($app->auth->user->divisionprimary == 0) {
            if ($divisionprimary !== $app->auth->user->divisionsecondary) {
                $app->auth->user->divisionprimary = $divisionprimary;               
            }
        } else {
            $this->get('flash')->addMessage('global_error', 'Profile NOT updated! Division can only be set once per season!');
            return $response->withRedirect($this->get('router')->pathFor('profile'));
        }
    } 

    if ($divisionsecondary !== $app->auth->user->divisionsecondary) {
        if ($app->auth->user->divisionsecondary == 0) {
            if ($divisionsecondary !== $app->auth->user->divisionprimary) {
                $app->auth->user->divisionsecondary = $divisionsecondary;    
            }
        } else {
            $this->get('flash')->addMessage('global_error', 'Profile NOT updated! Division can only be set once per season!');
            return $response->withRedirect($this->get('router')->pathFor('profile'));
        }
    }

    $ret = $app->auth->storeBean($app->auth->user);

    if ($ret) {
    	$this->get('flash')->addMessage('global', 'Profile updated!');
    	return $response->withRedirect($this->get('router')->pathFor('profile'));
    } else {
    	$this->get('flash')->addMessage('global_error', 'Profile NOT updated!');
    	return $response->withRedirect($this->get('router')->pathFor('profile'));
    }

})->setName('profile.post')->add($authenticated)->add(new GenCsrf);
