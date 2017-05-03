<?php

return array(

	'mode' => file_get_contents( ROOT . 'config/mode.php'),
	
	'default' => 'mysql',

	'connections' => array(

		'mysql_dev' => array(
			'driver'    => 'mysql',
			'host'      => 'localhost',
			'database'  => 'league',
			'username'  => 'root',
			'password'  => '123123',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => 'dev_',

		),

		'mysql_prod' => array(
			'driver'    => 'mysql',
			'host' => '64.90.60.135',
			'database' => 'fhtl_data',
			'username' => '',
			'password' => '',
			'charset' => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix' => 'fhtl_',
		),

	),

);
