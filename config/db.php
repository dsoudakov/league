<?php

return array(

	'mode' => file_get_contents( ROOT . 'config/mode.php'),
	
	'default' => 'mysql',

	'connections' => array(

		'mysql_dev' => array(
			'driver'    => 'mysql',
			'host'      => 'localhost',
			'database'  => 'test',
			'username'  => 'root',
			'password'  => '',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => 'dev_',

		),

		'mysql_prod' => array(
			'driver'    => 'mysql',
			'host' => '50.62.209.108',
			'database' => 'CATennis',
			'username' => 'dsoud',
			'password' => 'Ojqd4&41',
			'charset' => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix' => 'fhtl_',
		),

	),

);
