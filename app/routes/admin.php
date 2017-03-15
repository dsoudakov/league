<?php

//challenges
use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

$app->get('/admin[/{action}[/{userid}]]', function($request,$response,$args) use ($app)
{

	$v = $this->get('validator');

	if ($args['action'] == 'makeadmin') {

		$userid = $args['userid'];

	    $v->validate([
	        'userid|User id' => [$userid, 'required|int'],
	    ]);


	    if ($v->passes()) {
	    	$res = R::exec('UPDATE users SET is_admin = 1 WHERE id = :id', [':id' => $userid]);

	    	if ($res) {
	    		echo 'OK';	    		
	    	} else {

	    		echo 'FAILED TO MAKE ADMIN';
	    	}
	    }

	}

	if ($args['action'] == 'removeadmin') {

		$userid = $args['userid'];
		if ($userid == $app->user->id) {
			echo 'Stupid...';
			die();
		}

	    $v->validate([
	        'userid|User id' => [$userid, 'required|int'],
	    ]);


	    if ($v->passes()) {
	    	$res = R::exec('UPDATE users 
	    					SET is_admin = NULL 
	    					WHERE id = :id', 
	    					[
	    						':id' => $userid
	    					]);

	    	if ($res) {
	    		echo 'OK';	    		
	    	} else {

	    		echo 'FAILED TO REMOVE ADMIN';
	    	}
	    }

	}

	if ($args['action'] == 'leaguesettings') {

		return $this->view->render($response, 'admin/league.settings.twig');

	}

})->setName('admin.action.get')
  ->add($isAdmin)
  ->add($authenticated);


$app->post('/admin/leaguesettings', function($request,$response,$args) use ($app)
{


})->setName('admin.league.settings.post')
  ->add($isAdmin)
  ->add($authenticated);  