<?php

namespace CATL\Helpers;

use Mailgun\Mailgun;

class Mail
{
	protected $domain;
	protected $template;
	protected $message;
	protected $messageBody = [];
	protected $instance;

	public function __construct($config)
	{

		global $app;

		$cfg = $app->getContainer()->get('config');
		$first = $cfg->get('services.mailgun.first');
		$last = $cfg->get('services.mailgun.last');
		$from = $cfg->get('services.mailgun.from');
		
		$this->instance = new Mailgun($config);
		$this->domain = $cfg->get('services.mailgun.domain');
		$this->template();
		$this->message = $this->instance->MessageBuilder();
		$this->message->setFromAddress($from, ["first" => $first, "last" => $last]);
		$this->message();
	}

	public function send()
	{

		global $app;

		$view = $app->getContainer()->get('view');

		$html = $view->fetch($this->template, [
					'message' => $this->messageBody,
				]);

		$this->body($html);

		try {

			$mres = $this->instance->post("{$this->domain}/messages", 
											$this->message->getMessage(), 
											$this->message->getFiles()
				);
			return $mres;

		} catch (Exception $e) {

			// $this->flash->addMessage('global_error', 'Message was NOT sent: ' . $e->getMessage());
			// return $response->withRedirect($app->router->pathFor('home'));
			return false;
		}
		
	}

	public function subject($subject = 'No subject')
	{
		$this->message->setSubject($subject);
	}

	public function to($to, $first = '', $last = '')
	{
		$this->message->addToRecipient($to, ["first" => $first, "last" => $last]);
	}

	public function from($from, $first = '', $last = '')
	{
		$this->message->setFromAddress($from, ["first" => $first, "last" => $last]);
	}

	public function cc($cc, $first = '', $last = '')
	{
		$this->message->addCcRecipient($cc, ["first" => $first, "last" => $last]);
	}

	public function bcc($bcc, $first = '', $last = '')
	{
		$this->message->addBccRecipient($bcc, ["first" => $first, "last" => $last]);
	}

	public function template($template = 'templates/email/message.twig')
	{
		$this->template = $template;
	}

	public function body($body)
    {
        $this->message->setHtmlBody($body);
    }

	public function message($subject = 'League subject', $title = 'League title', $body = 'Message body.', $signature = '-- League Admin')
	{
		$this->subject($subject);
		$this->messageBody = [
			'subject' => $subject,
			'title' => $title,
			'body' => $body,
			'signature' => $signature,
		];
	}
}