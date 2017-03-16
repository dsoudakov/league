<?php

return [

	'app' => [
		'url' => 'http://soduakov.com/league',
		'hash' => [
			'algo' => PASSWORD_BCRYPT,
			'cost' => 10
		],
		'admin' => 'dsoudakov@gmail.com',
	],

	'mode' => file_get_contents(ROOT . 'config/mode.php'),

	'settings' => [
        'displayErrorDetails' => true,
    ],
	
	'url' => [
		'_dev' => 'http://localhost',
		'_prod' => 'http://soduakov.com',
		//'_prod' => 'http://localhost/league',
		
	],

	'services' => [
		'mailgun' => [
			'domain' => 'catennisleague.com',
			'secret' => 'key-0ff032421be087c89e6575629d6d6534',
			'first' => 'Tennis',
			'last' => 'League',
			'from' => 'postmaster@catennisleague.com',
		],
	],

	'rulesMessages' => [
		'unique' => '{field} "{value}" already exists!',
		'allowedToSend' => 'Such user doesn\'t exists!',
		'true' => '{field} is not checked.',
		'arrayOfInt' => 'Input error.',
	],

	'auth' => [
		'session' => 'user_id',
		'remember' => 'user_r',
		'cookieexpire' => '+1 month',
	],

];

