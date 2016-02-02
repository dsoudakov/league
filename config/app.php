<?php

return [

	'app' => [
		'url' => 'http://www.catennisleague.com/beta/authentication',
		'hash' => [
			'algo' => PASSWORD_BCRYPT,
			'cost' => 10
		]
	],

	'mode' => file_get_contents(ROOT . 'config/mode.php'),

	'settings' => [
        'displayErrorDetails' => true,
    ],
	
	'url' => [
		'_dev' => 'http://localhost',
		'_prod' => 'http://www.catennisleague.com/beta',
	],

	'db' => [
		'_prod' => [
			'mysql' => [
				'host' => 'localhost',
				'dbname' => 'test',
				'username' => 'root',
				'password' => '',
				'prefix' => 'selfdest_',	
			],
		],
		'_dev' => [
			'mysql_prod' => [
				'host' => '50.62.209.108',
				'dbname' => 'CATennis',
				'username' => 'dsoud',
				'password' => 'Ojqd4&41',
				'prefix' => 'selfdest_',
			],		
		],
	],

	'services' => [
		'mailgun' => [
			'domain' => 'catennisleague.com',
			'secret' => 'key-0ff032421be087c89e6575629d6d6534',
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

