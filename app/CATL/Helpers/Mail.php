<?php

namespace CATL\Helpers;

use Mailgun\Mailgun;

class Mail
{

	protected $template;
	protected $from = 'Tennis League <postmaster@catennisleague.com>';
	protected $subject;
	protected $message = [];
	protected $to;
	protected $bcc;
	protected $htmlBody;
	protected $instance;

	public function __construct($config)
	{
		$this->instance = new Mailgun($config);
		$this->template();
		$this->message();
	}

	public function send()
	{
		global $app;

		$config = $app->getContainer()->get('config');
		$view = $app->getContainer()->get('view');

		$html = $view->fetch($this->template, [
					'message' => $this->message,
				]);

		try {
			$mres = $this->instance->sendMessage($config->get('services.mailgun.domain') ,[
				'from' => $this->from,
				'to' => $this->to,
				'bcc' => $this->bcc,
				'subject' => $this->subject,
				'html' => $html,
			]);
			
			return $mres;

		} catch (Exception $e) {

			// $this->flash->addMessage('global_error', 'Message was NOT sent: ' . $e->getMessage());
			// return $response->withRedirect($app->router->pathFor('home'));
			return false;
		}
		
	}


	public function to($to)
	{
		$this->to = $to;
	}

	public function from($from)
	{
		$this->from = $from;
	}

	public function bcc($bcc)
	{
		$this->bcc = $bcc;
	}

	public function template($template = 'templates/email/message.twig')
	{
		$this->template = $template;
	}

	public function message($subject = 'League subject', $title = 'League title', $body = 'Message body.', $signature = '-- League Admin')
	{

		$this->subject = $subject;
		$this->message = [
			'subject' => $subject,
			'title' => $title,
			'body' => $body,
			'signature' => $signature,
		];
	}


}

