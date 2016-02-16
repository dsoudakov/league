<?php

use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

$app->get('/messageboard[/[{room}]]', function($request,$response,$args) use ($app)
{
	if ($args['room']) {
	    return $this->view->render($response, 'messageboard/board.twig', [
	    	'room' => $args['room'],
	    ]);
		
	} else {
		return $this->view->render($response, 'messageboard/board.twig');
	}

})->setName('messageboard')->add($isMember)->add($authenticated)->add(new GenCsrf);

$app->get('/mb/entries[/[{room}]]', function($request,$response,$args) use ($app)
{
	if ($args['room']) {
		$room = $args['room'];
	} else {
		$room = null;
	}

	if ($args['id']) {
	    $exp =  R::getAll( 'SELECT 
	    					m.id,
	    					m.room,
	    					m.message,
	    					m.createdat,
	    					u.first_name,
	    					u.last_name,
	    					u.email
	    					FROM messageboard m
	    					LEFT JOIN users u ON u.id = m.userid 
	    					 WHERE m.id = :messageid
	    					 ORDER by m.createdat DESC '
	    					 , [
    	':messageid' => $args['id'],
    	]);
	} else {
		if ($room) {
		    $exp =  R::getAll( 'SELECT 
		    					m.id,
		    					m.room,
		    					m.message,
		    					m.createdat,
		    					u.first_name,
		    					u.last_name,
		    					u.email

		    					FROM messageboard m
		    					LEFT JOIN users u on u.id = m.userid 
		    					WHERE m.room = :room
		    					ORDER by m.createdat DESC ',[
		    						':room' => $room,
		    					] );
		} else {
		    $exp =  R::getAll( 'SELECT 
		    					m.id,
		    					m.room,
		    					m.message,
		    					m.createdat,
		    					u.first_name,
		    					u.last_name,
		    					u.email,
		    					"1" as csrf_name,
		    					"2" as csrf_value
		    					FROM messageboard m
		    					LEFT JOIN users u on u.id = m.userid 
		    					ORDER by m.createdat DESC ' );
		}

	}
    echo json_encode($exp);

})->setName('mb_entries_id')->add($isMember)->add($authenticated);

$app->post('/mb/entries[/[{room}][/[{id}]]]]', function($request,$response,$args) use ($app)
{
	$v = $this->get('validator');
    
	$message = $request->getParam('message');
	$room = $request->getParam('room');

	$v->validate([
        'message|Message' => [$message, 'required|max(100)'],
        'room|Room' => [$room, 'required|max(50)'],
    ]);
	if ($v->passes()) {
	
	    $m = R::dispense('messageboard');
	    $m->userid = $app->user->id;
	    $m->room = $room;
	    $m->message = $message;
	    $m->createdat = Carbon::now('America/Toronto')->toDateTimeString();
    
	    R::store($m);
	    echo 'posted';
		
	} else {
		echo 'validation failed';
	}

})->setName('mb_post')->add($isMember)->add($authenticated);

