<?php


$app->get('/message/{hash}', function ($request,$response,$args)
{
	$m  = R::findOne('messages', ' hash = :hash ', [ 
		':hash' => $args['hash'],
	]);

	if ($m) {
		R::trash($m);
	}

	return $this->view->render($response, 'message/show.twig', [
		'm' => $m,
	]);

})->setName('message');


$app->post('/post', function ($request,$response,$args) use ($app) {

	$params = $request->getParams();
	$hash = md5(uniqid(true));

	$v = $app->validator;

	$v->validate([
	    'email|Email' => [$params['email'], 'required|email|allowedToSend'],
	    'message|Message' => [$params['message'], 'required|min(1)|max(1000)'],
	]);

	if ($v->fails()) {
		$this->view->render($response, 'home.twig',[
			'errors' => $v->errors(),
			'request' => $params,
			'global_error' => 'Message was not sent! Validation failed!'
		]);
		return;
	}

	$m = R::dispense('messages');
	
	$m->hash = $hash;
	$m->message = $params['message'];
	
	R::store($m);

	try {
		$mres = $this->mail->sendMessage($this->config->get('services.mailgun.domain') ,[
			'from' => 'postmaster@catennisleague.com',
			'to' => $params['email'],
			'subject' => 'New message from League',
			'html' => $this->view->fetch('email/message.twig', [
				'hash' => $hash,
			])
		]);
		
	} catch (Exception $e) {

		$this->flash->addMessage('global_error', 'Message was NOT sent: ' . $e->getMessage());
		return $response->withRedirect($app->router->pathFor('home'));
	}

	$this->flash->addMessage('global', 'Message was sent!');
	return $response->withRedirect($app->router->pathFor('home'));

})->setName('send');
