<?php
header('Access-Control-Allow-Origin: *');  

$app->get('/quote', function ($request,$response,$args)
{

	//$newresponse = $response->withAddedHeader('Access-Control-Allow-Origin', 'http://catennisleague.com');

	$q = Unirest\Request::post("https://andruxnet-random-famous-quotes.p.mashape.com/cat=famous",
	  array(
	    "X-Mashape-Key" => "PK2cWqvk5emshMJW5nxsna3oEY52p1r0gWpjsnYEtdrsgwgx2P",
	    "Content-Type" => "application/x-www-form-urlencoded",
	    "Accept" => "application/json"
	  )
	);

	$quoteBody = $q->body->quote;
	$quoteAuthor = $q->body->author;

	$this->view->render($response, 'quote.twig', [
		'quote' => $quoteBody,
		'author' => $quoteAuthor,
	]);

})->setName('quote');
