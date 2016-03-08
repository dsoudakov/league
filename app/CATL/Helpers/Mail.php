<?php

namespace CATL\Helpers;

use Mailgun\Mailgun;
use Carbon\Carbon;

class Mail
{
	public $days;
	protected $domain;
	protected $template;
	protected $templateBody;
	protected $message;
	protected $instance;

	public function __construct($config)
	{

		global $app;

		$this->templateBody = [
			'subject' => 'Test title 1',
			'title' => 'Test body title 1',
			'body' => 'Test message 1',
			'signature' => 'League admin',
		];

		$this->days = [
		    'Sunday',
		    'Monday',
		    'Tuesday',
		    'Wednesday',
		    'Thursday',
		    'Friday',
		    'Saturday',
		];

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

	public function mailErrorToAdmin($errormessage)
	{
		global $app;

		$now = Carbon::now('America/Toronto');

		$cfg = $app->getContainer()->get('config');
		
		$this->message->setSubject('ERROR IN LEAGUE');
		$this->templateBody = [
			'subject' => 'ERROR IN LEAGUE',
			'title' => 'ERROR for user: ' . $app->auth->id . ', ' . $app->user->email,
			'body' => $errormessage,
			'signature' => $now->toDateTimeString(),
		];

		$this->to($cfg->get('app.admin'));

		$this->send();

	}

	public function send()
	{

		global $app;

		$view = $app->getContainer()->get('view');

		$html = $view->fetch($this->template, [
					'message' => $this->templateBody,
				]);

		$this->body($html);

		try {

			$mres = $this->instance->post("{$this->domain}/messages",
											$this->message->getMessage(),
											$this->message->getFiles()
				);
			return $mres;

		} catch (Exception $e) {
			Audit::log($e->getMessage());
			//$this->mailErrorToAdmin($e->getMessage());
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

	public function toA($tos = [])
	{
		foreach ($tos as $to) {

			$this->to($to, '', '');

		}
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

	public function message($templateBody = [])
	{
		if (!$templateBody) {
			$templateBody = $this->templateBody;
		}

		$this->subject($templateBody['subject']);
		$this->templateBody = [
			'subject' => $templateBody['subject'],
			'title' => $templateBody['title'],
			'body' => $templateBody['body'],
			'signature' => $templateBody['signature'],
		];

	}
}