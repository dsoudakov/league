<?php

$app->get('/', function ($request,$response,$args) use ($app)
{
	
	$response = $this->view->render($response, 'home.twig', []);

	return $response;

})->setName('home');


$app->get('/about', function ($request,$response,$args) use ($app)
{
	
	$response = $this->view->render($response, 'about.twig', []);

	return $response;

})->setName('about');


$app->get('/aboutclub', function ($request,$response,$args) use ($app)
{
	
	$response = $this->view->render($response, 'aboutclub.twig', []);

	return $response;

})->setName('about.club');

$app->get('/abouttest', function ($request,$response,$args) use ($app)
{
		$hour_offset_mysql = '+ INTERVAL 3 HOUR';

        $sql = 'SELECT 
                    concat(u.first_name, \' \', u.last_name, \' (\', u.email, \')\') as email 
                    FROM usersactive ua 
                    LEFT JOIN users u on u.id = ua.user_id 
                    WHERE last_active > (NOW() '. $hour_offset_mysql  .' - INTERVAL 15 MINUTE)';

        $ua = R::getAll($sql);

        foreach ($ua as $v) {
            $out .= $v['email'] . BR;
        }

        var_dump($out);		

})->setName('about.test');