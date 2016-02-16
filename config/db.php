<?php

return array(

	'mode' => file_get_contents( ROOT . 'config/mode.php'),
	
	'default' => 'mysql',

	'connections' => array(

		'sqlite_dev' => array(
			'driver'   => 'sqlite',
			'database' => ROOT . 'app/storage/db/test.s3db',
			'prefix'   => '',
		),

		'mysql_dev' => array(
			'driver'    => 'mysql',
			'host'      => isset($_SERVER['DB1_HOST']) ? $_SERVER['DB1_HOST'] : 'localhost',
			'database'  => isset($_SERVER['DB1_NAME']) ? $_SERVER['DB1_NAME'] : 'test',
			'username'  => isset($_SERVER['DB1_USER']) ? $_SERVER['DB1_USER'] : 'root',
			'password'  => isset($_SERVER['DB1_PASS']) ? $_SERVER['DB1_PASS'] : '',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => 'selfdest_',

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

		'pgsql_dev' => array(
			'driver'   => 'pgsql',
			'host'     => 'localhost',
			'database' => 'database',
			'username' => 'root',
			'password' => '',
			'charset'  => 'utf8',
			'prefix'   => '',
			'schema'   => 'public',
		),

		'sqlsrv_dev' => array(
			'driver'   => 'sqlsrv',
			'host'     => 'localhost',
			'database' => 'database',
			'username' => 'root',
			'password' => '',
			'prefix'   => '',
		),

	),


);
