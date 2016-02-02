<?php

namespace CATL\Helpers;

class Hash
{
	protected $config;

	public function __construct($config)
	{
		$this->config = $config;

		if(!function_exists('hash_equals')) {
		  function hash_equals($str1, $str2) {
		    if(strlen($str1) != strlen($str2)) {
		      return false;
		    } else {
		      $res = $str1 ^ $str2;
		      $ret = 0;
		      for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
		      return !$ret;
		    }
		  }
		}

	}

	public function password($password)
	{
		return password_hash(
			$password,
			$this->config->get('app.hash.algo'),
			['cost' => $this->config->get('app.hash.cost')]
		);
	}

	public function passwordCheck($password, $hash)
	{
		return password_verify($password, $hash);
	}

	public function hash($input)
	{
		return hash('sha256', $input);
	}

	public function hashCheck($known, $user)
	{
		return hash_equals($known, $user);
	}
}
