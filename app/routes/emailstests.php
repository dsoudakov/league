<?php

// email templates tester

use CATL\Models\User;
use CATL\Helpers\Audit;

$app->get('/t1', function($request,$response,$args) use ($app)
{
	// note::: $this = $app->getContainer();
	$template = [
		'subject' => 'Test title 1',
		'title' => 'Test body title 1',
		'body' => 'Test message 1',
		'signature' => 'League admin',
	];

	$mail = $this->get('mail2');

	$mail->to('');
	$mail->bcc('dsoudakov@gmail.com');
	$mail->bcc('fthc.catl@gmail.com');


	/*dump($mail);
	die();*/
	$res = $mail->send();

	//Audit::log('Mail sent, result: ' . $res->http_response_code);

	echo $res->http_response_body->message;

	dump($res);
	die();



	return $this->view->render($response, 'templates/email/message.twig', [
		'message' => $template,
	]);

})->setName('general.email.template')->add($authenticated);


