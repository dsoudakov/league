<?php

namespace CATL\Helpers;

use Mailgun\Mailgun;

class Mail
{

	protected $instance;

	public function __construct($config)
	{
		this->instance = new Mailgun($config);
	}

	public static function send($dest)
	{

		
	}

	public function subject($subject)
	{
		
	}
}

