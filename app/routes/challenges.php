<?php

//challenges
use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

// challenge group of 1+ players from your division(s)
// on specific date (and x number of matches, possibly)
$app->get('/createchallenge', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'challenge/challenge.create.twig', []);

})->setName('challenge.create')->add($isMember)->add($authenticated)->add(new GenCsrf);

$app->get('/challengeall', function($request,$response,$args) use ($app)
{
	return $this->view->render($response, 'challenge/challengeall.twig', [
		'challengeInDivision' => $_SESSION['challengeInDivision'],
	]);

})->setName('challenge.create.all')->add($isMember)->add($authenticated)->add(new GenCsrf);

$app->post('/challengeall', function($request,$response,$args) use ($app)
{
	$challengeInDivision =  $request->getParam('challengeInDivision');
	$challengedate1 = $request->getParam('challengedate');
	$challengedate = Carbon::parse($request->getParam('challengedate'));
	$today = Carbon::today('America/Toronto');
	$lastDate = Carbon::today('America/Toronto')->addWeeks(2);

    // echo "CD:".$challengedate1 . BR;
	// echo "TD:".$today . BR;

	$test = Carbon::parse($challengedate)->gte($today);
	$test2 = Carbon::parse($challengedate)->lte($lastDate);

	// var_dump($test) . BR;
	// var_dump($test2). BR;

	$challengenote = $request->getParam('challengenote');

	$v = $this->get('validator');

	$v->validate([
		'challengenote|Challenge note' => [$challengenote, 'required|max(200)'],
		'challengedate1|Challenge date' => [$challengedate1, 'required|max(30)'],
		'challengeInDivision|Challenged division' => [$challengeInDivision, 'required|between(1,99)'],
	]);

	if ($v->passes() && $test && $test2) {
		$now = Carbon::now('America/Toronto');
		
		$cc = R::findall('challenges', ' challengerid = :id and challengedate = :date and challenge_in_division = :challengeInDivision ', [
			':id' => $app->auth->id,
			':date' => $challengedate->toDateTimeString(),
			':challengeInDivision' => $challengeInDivision,
		]);

		if (!empty($cc)) {

	    	$this->get('flash')->addMessage('global_error', 'You have already created a challenge for this date and division. Check your challenges. ');
	   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.all'));
		}

		$c = R::dispense('challenges');
		$c->challengerid = $app->auth->id;
		$c->challengeInDivision = $challengeInDivision;
		$c->challengedate = $challengedate;
		$c->challengenote = $challengenote;
		$c->challengecreatedat = $now;

		if (User::storeBean($c)) {
	    	
	    	$this->get('flash')->addMessage('global', 'Challenge created successfully.');
	   		return $response->withRedirect($this->get('router')->pathFor('challenges.my'));
			
		} else {
	    	$this->get('flash')->addMessage('global_error', 'Failed to create challenge. Please try again.');
	   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.all'));
		}
		

	} else {
		if (!$test || !$test2) {
	    	$this->get('flash')->addMessage('global_error', 'Challenge date is incorrect.');
	   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.all'));
		}
    	$this->get('flash')->addMessage('global_error', 'Challenge date and note are required.');
   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.all'));

	}

})->setName('challenge.create.all.post')->add($isMember)->add($authenticated)->add(new GenCsrf);

$app->get('/challengespecific', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'challenge/challengespecific.twig', [
		'challengeInDivision' => $_SESSION['challengeInDivision'],
	]);

})->setName('challenge.create.specific')->add($isMember)->add($authenticated)->add(new GenCsrf);

$app->post('/challengespecific', function($request,$response,$args) use ($app)
{

	$challengedids =  $request->getParam('challengedids');

	$challengeInDivision =  $request->getParam('challengeInDivision');
	$challengedate1 = $request->getParam('challengedate');
	$challengedate = Carbon::parse($request->getParam('challengedate'));
	$today = Carbon::today('America/Toronto');
	$lastDate = Carbon::today('America/Toronto')->addWeeks(2);

    // echo "CD:".$challengedate1 . BR;
	// echo "TD:".$today . BR;

	$test = Carbon::parse($challengedate)->gte($today);
	$test2 = Carbon::parse($challengedate)->lte($lastDate);

	// var_dump($test) . BR;
	// var_dump($test2). BR;

	$challengenote = $request->getParam('challengenote');

	$v = $this->get('validator');

	$v->validate([
		'challengenote|Challenge note' => [$challengenote, 'required|max(200)'],
		'challengedate1|Challenge date' => [$challengedate1, 'required'],
		'challengedids' => [$challengedids, 'required|array|arrayOfInt'],
		'challengeInDivision|Challenged division' => [$challengeInDivision, 'required|between(1,99)'],
	]);		

	$_SESSION['challengenote'] = $challengenote;
	$_SESSION['challengedate'] = $challengedate1;
	$_SESSION['checkedids'] = $challengedids;

	if ($v->passes() && $test && $test2) {

		$now = Carbon::now('America/Toronto');
		
		$cc = R::findall('challenges', ' challengerid = :id and challengedate = :date and challenge_in_division = :cid ', 
		[
			':id' => $app->user->id,
			':date' => $challengedate->toDateTimeString(),
			':cid' => $challengeInDivision,
		]);

		if (!empty($cc)) {

	    	$this->get('flash')->addMessage('global_error', 'You have already created a challenge for this date and division. Check your existing challenges. ');
	   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.specific'));
		}

		if ($challengeInDivision == $app->user->divisionprimary || $challengeInDivision == $app->user->divisionsecondary) {
			
		} else {
	    	$this->get('flash')->addMessage('global_error', 'You are not allow to challenge these divisions.');
	   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.specific'));
		}

		$c = R::dispense('challenges');
		$c->challengerid = $app->auth->id;
		$c->challengeInDivision = $challengeInDivision;
		$c->challengedate = $challengedate;
		$c->challengenote = $challengenote;
		$c->challengecreatedat = $now;
		$c->challengedids = json_encode($challengedids);

		if (User::storeBean($c)) {

			unset($_SESSION['challengenote']);
			unset($_SESSION['challengedate']);
			unset($_SESSION['checkedids']);
	    	
	    	$this->get('flash')->addMessage('global', 'Challenge created successfully.');
	   		return $response->withRedirect($this->get('router')->pathFor('challenges.my'));
			
		} else {
	    	$this->get('flash')->addMessage('global_error', 'Failed to create challenge. Please try again.');
	   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.specific'));
		}
		
	} else {
		if (!$test || !$test2) {
	    	$this->get('flash')->addMessage('global_error', 'Challenge date is incorrect.');
	   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.specific'));
		}
		
		if ($challengedids == null) {
			$this->get('flash')->addMessage('global_error', 'No players selected!');
			return $response->withRedirect($this->get('router')->pathFor('challenge.create.specific'));
		}

    	$this->get('flash')->addMessage('global_error', 'Failed to create challenge. Please try again. '. $v->errors()->first());
   		return $response->withRedirect($this->get('router')->pathFor('challenge.create.specific'));
	}

})->setName('challenge.create.specific.post')->add($isMember)->add($authenticated)->add(new GenCsrf);

// existing challenges other players have issued that include you as challenged for a current date or yyyymmdd date,
// will show if status: open (issuer can close challenge because enough challanges are confirmed)
// will show button accept if open
$app->get('/challenges[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'challenge/challenges.issued.twig', []);

})->setName('challenges.home')->add($isMember)->add($authenticated)->add(new GenCsrf);

// my issued/accepted/confirmed challenges for a current date or yyyymmdd date
// show challenges I issued
// - I accepted and issuer response status
// - I confirmed (when, what note give, time) <- should be on top
$app->get('/mychallenges[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{
	//echo 'Hello. '.$args['yyyymmdd'];
	return $this->view->render($response, 'challenge/challenges.my.twig', []);

})->setName('challenges.my')
  ->add($isMember)
  ->add($authenticated)
  ->add(new GenCsrf);


//challenges.my.get.json

$app->get('/mychallengesjson[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{
	//$ddate = Carbon::parse($args['yyyymmdd']);
	
	if (!empty($args['yyyymmdd'])) {

		//echo $ddate;

	} else {

		$mychallenges = R::getAll( 'SELECT  
										c.id AS challengeid, 
										concat(Date(challengedate), \' (\', dayname(Date(challengedate)), \') \') AS challengedate, 										
										d.divisiondesc AS challengeddivision,
										challengenote, 
										challengecreatedat,
										count(ac.id) as numofacceptedchallenges,
										IFNULL(sum(ac.confirmed),0) as numofconfirmedchallenges,
										count(ac.cancelnote) as numofcancelledchallenges,
								LENGTH(c.challengedids) - LENGTH(REPLACE(c.challengedids, \'\,\', \'\')) + 1 as numofplayers,
										ac.confirmed,
										c.cancelnote  
								FROM challenges c 
								LEFT JOIN divisions d on c.challenge_in_division = d.id 
								LEFT JOIN acceptedchallenges ac on c.id = ac.acceptedchallengeid 
								WHERE challengerid = :challengerid 
								Group by challengeid, challengedate, challengeddivision, challengenote,challengecreatedat
								', [
								':challengerid' => $app->auth->id,
		]);

		echo json_encode($mychallenges);		

	}

})->setName('challenges.my.get.json')
  ->add($isMember)
  ->add($authenticated)
  ->add(new GenCsrf);

$app->get('/myacceptedchallengesjson[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{
	//$ddate = Carbon::parse($args['yyyymmdd']);
	
	if (!empty($args['yyyymmdd'])) {

		//echo $ddate;

	} else {

		$myacceptedchallenges = R::getAll( 'SELECT 
								ac.acceptedchallengeid as challengeid,
								ac.id as acceptedchallengeid,
								concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate, 									
								concat(u.first_name, \' \', u.last_name) as challenger,	
								d.divisiondesc as challengeddivision,
								ac.acceptednote as challengenote,
								ac.confirmed,
								ac.cancelnote 
								FROM acceptedchallenges ac 
								LEFT JOIN challenges c on c.id = ac.acceptedchallengeid 
								LEFT JOIN divisions d on d.id = c.challenge_in_division 
								LEFT JOIN users u on u.id = c.challengerid 
								WHERE ac.acceptedbyuserid = :uid 
								AND c.cancelnote IS NULL
								
								', [
								':uid' => $app->user->id,
		]);

		echo json_encode($myacceptedchallenges);		

	}

})->setName('acceptedchallenges.my.get.json')
  ->add($isMember)
  ->add($authenticated);

$app->get('/issuedchallengesjson[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{
	//$ddate = Carbon::parse($args['yyyymmdd']);
	
	if (!empty($args['yyyymmdd'])) {

		//echo $ddate;

	} else {

		$issuedchallenges = R::getAll( 'SELECT  
										c.id AS challengeid, 
										concat(u.first_name, \' \', u.last_name) as challenger,
										concat(Date(challengedate), \' (\', dayname(Date(challengedate)), \') \') AS challengedate, 
										concat(d.divisiondesc,  \' (\', d.divisionname,  \') \') AS challengeddivision,
										challengenote, 
										challengecreatedat,
										c.challenge_in_division,
										ac.acceptedbyuserid,
										ac.confirmed,
										ac.cancelnote,
										LENGTH(c.challengedids) - LENGTH(REPLACE(c.challengedids, \'\,\', \'\')) + 1 as numofplayers
								FROM challenges c 
								LEFT JOIN users u on c.challengerid = u.id 
								LEFT JOIN divisions d on c.challenge_in_division = d.id 
								LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id and ac.acceptedbyuserid = :cid 
								WHERE Date(challengedate) BETWEEN CURDATE() AND CURDATE() + INTERVAL 15 DAY
										and challengerid <> :cid 
										and (c.challengedids like \'%"' . $app->user->id . '"%\' or c.challengedids IS NULL )
								AND c.cancelnote IS NULL 
								', 
								[
									':cid' => $app->user->id,
								]);

		echo json_encode($issuedchallenges);		

	}
})->setName('challenges.issued.get.json')
  ->add($isMember)
  ->add($authenticated)
  ->add(new GenCsrf);

$app->get('/challenge[/{action}[/{challengeid}]]', function($request,$response,$args) use ($app)
{
	
	$v = $this->get('validator');

	if ($args['action'] == 'delete') {

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

	    if ($v->passes()) {

			$challenges = R::getRow( 'SELECT  *
						FROM challenges c 
						LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id 
						LEFT JOIN users u on c.challengerid = u.id 
						LEFT JOIN divisions d on c.challenge_in_division = d.id
						WHERE c.challengerid = :uid and c.id = :cid ', 
						[
							':uid' => $app->user->id,
							':cid' => $args['challengeid'],
						]);

			if ($challenges) {

				if ($challenges['confirmed'] == 1) {
					$this->get('flash')->addMessage('global_error', 'Challenge NOT deleted! Cannot delete confirmed challenges. Can only cancel with reasons.');
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					return $response;	
				}
			
				return $this->view->render($response, 'challenge/challenge.delete.partial.twig', [
					'challengeid' => $args['challengeid'],
				]);


			}

	    } else {
	    	//dump($v->errors());
	    	//echo 'validation failed';
	    }
	
	}

	if ($args['action'] == 'cancel') {

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

	    if ($v->passes()) {

			$challenges = R::getRow( 'SELECT  *
						FROM challenges c 
						LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id 
						LEFT JOIN users u on c.challengerid = u.id 
						LEFT JOIN divisions d on c.challenge_in_division = d.id
						WHERE c.challengerid = :uid and c.id = :cid 
						AND ac.reportconfirmed IS NULL
						AND ac.reportedbyuserid IS NULL
						', 
						[
							':uid' => $app->user->id,
							':cid' => $args['challengeid'],
						]);

			if ($challenges) {

				if ($challenges['confirmed'] == 1) {
					return $this->view->render($response, 'challenge/challenge.cancel.partial.twig', [
						'challengeid' => $args['challengeid'],
					]);
				}
			} else {
				echo '<h3 class="label-danger">Cannot cancel this challenge!</h3>';
			}

	    } else {
	    	//dump($v->errors());
	    	//echo 'validation failed';
	    }
	
	}

	if ($args['action'] == 'cancelaccepted') {

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

	    if ($v->passes()) {

			$challenges = R::getRow( 'SELECT  *
						FROM challenges c 
						LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id 
						LEFT JOIN users u on c.challengerid = u.id 
						LEFT JOIN divisions d on c.challenge_in_division = d.id
						WHERE ac.id = :cid AND ac.acceptedbyuserid = :uid AND ac.cancelnote is null ', 
						[
							':uid' => $app->user->id,
							':cid' => $args['challengeid'],
						]);

			if ($challenges) {

					return $this->view->render($response, 'challenge/challenge.cancelaccepted.partial.twig', [
						'challengeid' => $args['challengeid'],
					]);
			} else {

				echo 'Beeeeeep...';
			}

	    } else {
	    	//dump($v->errors());
	    	//echo 'validation failed';
	    }
	
	}

	if ($args['action'] == 'accept') {


		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

	    if ($v->passes()) {
			$canacceptchallenge = R::getRow( 'SELECT  *
						FROM challenges c LEFT JOIN users u on c.challengerid = u.id 
						LEFT JOIN divisions d on c.challenge_in_division = d.id
						WHERE (u.divisionprimary = :divp or  u.divisionsecondary = :divs) and c.id = :cid ', 
						[
							':cid' => $args['challengeid'],
							':divp' => $app->user->divisionprimary,
							':divs' => $app->user->divisionsecondary,
						]);

			if ($canacceptchallenge) {

				$checkalreadyaccepted = R::getRow( ' SELECT * FROM acceptedchallenges 
										WHERE acceptedchallengeid = :acid and acceptedbyuserid = :abuid

				', [

					':acid' => $args['challengeid'],
					':abuid' => $app->user->id,

				]);

				if ($checkalreadyaccepted) {
					echo 'Already accepted!';
				} else {

					return $this->view->render($response, 'challenge/challenge.accept.twig', [
						'challengeid' => $args['challengeid'],
					]);

				}



			} else {
				echo 'Cannot accept this challenge due to reason... :)';
			}
	    } else {
			echo 'Errors';
	    }

	}

	if ($args['action'] == 'confirm') {

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

	    if ($v->passes()) {
			$canconfirmchallenge = R::getRow( 'SELECT  *, ac.cancelnote as accancelnote
						FROM acceptedchallenges ac 
						LEFT JOIN challenges c on c.id = ac.acceptedchallengeid 
						WHERE c.challengerid = :uid and ac.id = :cid ',
						[
							':cid' => $args['challengeid'],
							':uid' => $app->user->id,
						]);

			if ($canconfirmchallenge) {
//                dump($canconfirmchallenge);
//                die();
				return $this->view->render($response, 'challenge/challenge.confirm.twig', [
					'challengeid' => $args['challengeid'],
                    'c' => $canconfirmchallenge,
				]);				
			} else {

                //echo '<h2>Can\'t confirm!</h2>';
//				$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
//				return $response;
			}

		}

	}

	if ($args['action'] == 'details') {	
		
		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

		if ($v->passes()) {

			$challenge = R::getRow( 'SELECT  
										c.id AS challengeid, 
										concat(u.first_name, \' \', u.last_name) as challenger,
										concat(Date(challengedate), \' (\', dayname(Date(challengedate)), \') \') AS challengedate, 
										concat(d.divisiondesc,  \' (\', d.divisionname,  \') \') AS challengeddivision,
										challengenote, 
										challengecreatedat,
										c.challenge_in_division 
								FROM challenges c 
								LEFT JOIN users u on c.challengerid = u.id 
								LEFT JOIN divisions d on c.challenge_in_division = d.id 
								WHERE c.id = :cid and (c.challenge_in_division = u.divisionprimary or c.challenge_in_division = u.divisionsecondary)', 
								[
									':cid' => $args['challengeid'],						
								]);
			

			return $this->view->render($response, 'challenge/challenge.details.partial.twig', [
				'challengeid' => $args['challengeid'],
				'challenger' => $challenge['challenger'],
				'challengedate' => $challenge['challengedate'],
				'challengeddivision' => $challenge['challengeddivision'],
				'challengenote' => $challenge['challengenote'],
			]);
		}
	    
	}
	
	if ($args['action'] == 'detailsaccepted') {	
		
		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

		if ($v->passes()) {

			$challenge = R::getRow( 'SELECT  
										c.id AS challengeid, 
										concat(u.first_name, \' \', u.last_name) as challenger,
										concat(Date(challengedate), \' (\', dayname(Date(challengedate)), \') \') AS challengedate, 
										concat(d.divisiondesc,  \' (\', d.divisionname,  \') \') AS challengeddivision,
										challengenote, 
										challengecreatedat,
										c.challenge_in_division 
								FROM challenges c 
								LEFT JOIN acceptedchallenges ac ON ac.acceptedchallengeid = c.id
								LEFT JOIN users u on c.challengerid = u.id 
								LEFT JOIN divisions d on c.challenge_in_division = d.id 
								WHERE ac.id = :cid and (c.challenge_in_division = u.divisionprimary or c.challenge_in_division = u.divisionsecondary)
								AND ac.cancelnote is null
								 ', 
								[
									':cid' => $args['challengeid'],						
								]);
			
			if ($challenge) {
				return $this->view->render($response, 'challenge/challenge.details.partial.twig', [
					'challengeid' => $args['challengeid'],
					'challenger' => $challenge['challenger'],
					'challengedate' => $challenge['challengedate'],
					'challengeddivision' => $challenge['challengeddivision'],
					'challengenote' => $challenge['challengenote'],
				]);
			}
		}
	    
	}

	if ($args['action'] == 'confirmdetails') {	
		
		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

		if ($v->passes()) {

			$challenge = R::getRow( 'SELECT  
										ac.id AS challengeid, 
										concat(u.first_name, \' \', u.last_name) as challenger,
										concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate, 
										concat(d.divisiondesc,  \' (\', d.divisionname,  \') \') AS challengeddivision,
										ac.acceptednote as challengenote, 
										c.challengecreatedat,
										c.challenge_in_division 
								FROM acceptedchallenges ac 
								LEFT JOIN challenges c on c.id = ac.acceptedchallengeid 
								LEFT JOIN users u on ac.acceptedbyuserid = u.id 
								LEFT JOIN divisions d on c.challenge_in_division = d.id 
								WHERE ac.id = :cid and (c.challenge_in_division = u.divisionprimary or c.challenge_in_division = u.divisionsecondary)
								AND ac.cancelnote is null
								', 
								[
									':cid' => $args['challengeid'],						
								]);
			
			if ($challenge) {
				return $this->view->render($response, 'challenge/challenge.confirmdetails.partial.twig', [
					'challengeid' => $args['challengeid'],
					'challenger' => $challenge['challenger'],
					'challengedate' => $challenge['challengedate'],
					'challengeddivision' => $challenge['challengeddivision'],
					'challengenote' => $challenge['challengenote'],
				]);				
			}

		}
	    
	}

	if ($args['action'] == 'confirmeddetails') {	
		
		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

		if ($v->passes()) {

			$challenges = R::getAll( 'SELECT  
										c.id AS challengeid,
										ac.id as acceptedchallengeid,
										concat(uu.first_name, \' \', uu.last_name) as challenger,
										ac.acceptednote, 
										ac.confirmnote,
										challengecreatedat,
										ac.acceptedat,
										ac.confirmedat,
										ac.confirmed,
										c.cancelnote,
										ac.cancelnote as accancelnote,
										u.id as userid

								FROM acceptedchallenges ac 
								LEFT JOIN challenges c on ac.acceptedchallengeid = c.id 
								LEFT JOIN users u on c.challengerid = u.id 
								LEFT JOIN users uu on ac.acceptedbyuserid = uu.id
								WHERE ac.acceptedchallengeid = :cid and (c.challenge_in_division = u.divisionprimary or c.challenge_in_division = u.divisionsecondary) 
									and (c.challengerid = :uid 
										OR c.challengedids like \'%"'. $app->user->id .'"%\' 
										OR c.challengedids is NULL )
									ORDER by ac.confirmed DESC
								', 
								[
									':cid' => $args['challengeid'],						
									':uid' => $app->user->id,
								]);
			
			// dump($challenges);
			// die();

			return $this->view->render($response, 'challenge/challenge.confirmeddetails.partial.twig', [
				'challenges' => $challenges,
			]);
		}
	}	

})->setName('challenge.action.get')->add($isMember)->add($authenticated)->add(new GenCsrf);

// issue a challenge
$app->post('/challenge[/{action}[/{challengeid}]]', 
	function($request,$response,$args) 
	use ($app)
{

	$v = $this->get('validator');

	if ($args['action'] == 'create') {

		$challengeInDivision = $request->getParam('challengeInDivision');

	    $v->validate([
	        'challengeInDivision|Challenged division' => [$challengeInDivision, 'required|between(1,99)'],
	    ]);


	    if ($v->passes()) {

			$_SESSION['challengeInDivision'] = $challengeInDivision;
			$response = $response->withRedirect($this->get('router')->pathFor('challenge.'. $args['action'] .'.'.$args['challengeid']));
			return $response;
    	
	    } else {

			$this->get('flash')->addMessage('global_error', 'Errors... Failed to create challenge.');
			$response = $response->withRedirect($this->get('router')->pathFor('challenge.create'));
			return $response;

		}

	}

	if ($args['action'] == 'delete') {

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

	    if ($v->passes()) {

			$challenges = R::getRow( 'SELECT  *
						FROM challenges c 
						LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id 
						LEFT JOIN users u on c.challengerid = u.id 
						LEFT JOIN divisions d on c.challenge_in_division = d.id
						WHERE c.challengerid = :uid and c.id = :cid ', 
						[
							':uid' => $app->user->id,
							':cid' => $args['challengeid'],
						]);

			if ($challenges) {

				if ($challenges['confirmed'] == 1) {
					$this->get('flash')->addMessage('global_error', 'Challenge NOT deleted! Cannot delete confirmed challenges. Can only cancel with reasons.');
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					return $response;	
				}
				//dump($challenges);
				//die('delete would succeed.');	

			    R::begin();
			    try{
			        R::exec( 'DELETE FROM challenges where id = ' . $args['challengeid'] );
			        R::exec( 'DELETE FROM acceptedchallenges where acceptedchallengeid = ' . $args['challengeid'] );
			        R::commit();
					$this->get('flash')->addMessage('global', 'Challenge deleted!');
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					return $response;			        
			    }
			    catch( Exception $e ) {
			        R::rollback();
					$this->get('flash')->addMessage('global_error', 'Challenge NOT deleted! Error: ' . $e->getMessage());
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					return $response;			        

			    }
			}

	    } else {
	    	//dump($v->errors());
	    	//echo 'validation failed';
	    }
	
	}	 

	if ($args['action'] == 'cancel') {

		$challengeCancelNote = $request->getParam('challengeCancelNote');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	        'challengeCancelNote| Cancel note' => [$challengeCancelNote, 'required|max(200)'],
	    ]);

	    if ($v->passes()) {

			$challenge = R::getRow( 'SELECT  *
						FROM challenges c 
						LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id 
						LEFT JOIN users u on c.challengerid = u.id 
						LEFT JOIN divisions d on c.challenge_in_division = d.id
						WHERE c.challengerid = :uid and c.id = :cid 
						AND ac.reportconfirmed IS NULL
						AND ac.reportedbyuserid IS NULL
						', 
						[
							':uid' => $app->user->id,
							':cid' => $args['challengeid'],
						]);

			if ($challenge) {

				if ($challenge['confirmed'] == 1) {

					$c = R::findOne('challenges', ' id = :id ', [
						':id' => $args['challengeid'],
					]);

				    R::begin();
				    try{

				    	$c->cancelnote = $challengeCancelNote;
				    	R::store($c);
				        R::commit();
						$this->get('flash')->addMessage('global', 'Challenge cancelled!');
						$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						return $response;			        
				    }
				    catch( Exception $e ) {
				        R::rollback();
						$this->get('flash')->addMessage('global_error', 'Challenge NOT cancelled! Error: ' . $e->getMessage());
						$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						return $response;			        

				    }
				} else {
					// dump($c);
					// die('not confirmed');
				}
				//dump($challenges);
				//die('delete would succeed.');	
			}

	    } else {
			$this->get('flash')->addMessage('global_error', 'Challenge NOT cancelled! ' . implode(',', $v->errors()->all()));
			$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
			return $response;			        

	    }
	
	}	

	if ($args['action'] == 'cancelaccepted') {

		$challengeCancelAcceptedNote = $request->getParam('challengeCancelAcceptedNote');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	        'challengeCancelAcceptedNote| Cancel note' => [$challengeCancelAcceptedNote, 'required|max(200)'],
	    ]);

	    if ($v->passes()) {

			$challenge = R::getRow( 'SELECT  *
						FROM challenges c 
						LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id 
						LEFT JOIN users u on c.challengerid = u.id 
						LEFT JOIN divisions d on c.challenge_in_division = d.id
						WHERE ac.id = :cid AND ac.acceptedbyuserid = :uid AND ac.cancelnote is null ', 
						[
							':uid' => $app->user->id,
							':cid' => $args['challengeid'],
						]);

			if ($challenge) {

					$c = R::findOne('acceptedchallenges', ' id = :id ', [
						':id' => $args['challengeid'],
					]);

				    R::begin();
				    try{

				    	$c->cancelnote = $challengeCancelAcceptedNote;
                        //$c->confirmed = null;
				    	R::store($c);
				        R::commit();
						$this->get('flash')->addMessage('global', 'Challenge cancelled! Player notified.');
						$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						return $response;			        
				    }
				    catch( Exception $e ) {
				        R::rollback();
						$this->get('flash')->addMessage('global_error', 'Challenge NOT cancelled! Error: ' . $e->getMessage());
						$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						return $response;			        

				    }
			}

	    } else {
			$this->get('flash')->addMessage('global_error', 'Challenge NOT cancelled! ' . implode(',', $v->errors()->all()));
			$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
			return $response;			        

	    }
	
	}	

	if ($args['action'] == 'accept') {

		$challengeAcceptNote = $request->getParam('challengeAcceptNote');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	        'challengeAcceptNote|Accept note' => [$challengeAcceptNote, 'required|max(200)'],
	    ]);

	    if ($v->passes()) {
			$canacceptchallenge = R::getRow( 'SELECT  *
						FROM challenges c LEFT JOIN users u on c.challengerid = u.id 
						LEFT JOIN divisions d on c.challenge_in_division = d.id
						WHERE (u.divisionprimary = :divp or  u.divisionsecondary = :divs) and c.id = :cid ', 
						[
							':cid' => $args['challengeid'],
							':divp' => $app->user->divisionprimary,
							':divs' => $app->user->divisionsecondary,
						]);

			if ($canacceptchallenge) {

				$checkalreadyaccepted = R::getRow( ' SELECT * FROM acceptedchallenges 
										WHERE acceptedchallengeid = :acid and acceptedbyuserid = :abuid

				', [

					':acid' => $args['challengeid'],
					':abuid' => $app->user->id,

				]);

				if ($checkalreadyaccepted) {
					$this->get('flash')->addMessage('global_error', 'Challenge ALREADY accepted! Please refresh the page or go to My Challenges to see the status.');
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.home'));
					return $response;						
				}

				$c = R::dispense('acceptedchallenges');
				$c->acceptedchallengeid = $args['challengeid'];
				$c->acceptedbyuserid = $app->user->id;
				$c->acceptedat = Carbon::now('America/Toronto');
				$c->confirmed = 0;
				$c->acceptednote = $challengeAcceptNote;
				$c->confirmhash = $this->get('hash')->hash($this->get('randomlib')->generateString(128));
				$c->confirmedat = null;
				$c->confirmedbyuserid = 0;

			    R::begin();
			    try{
			        R::store($c);
			        R::commit();
					$this->get('flash')->addMessage('global', 'Challenge accepted!');
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.home'));
					return $response;			        
			    }
			    catch( Exception $e ) {
			        R::rollback();
					$this->get('flash')->addMessage('global_error', 'Challenge NOT accepted! Error: ' . $e->getMessage());
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.home'));
					return $response;			        

			    }

				//send email here to challenger with confirmation link and user info

			} else {
				$response = $response->withRedirect($this->get('router')->pathFor('challenges.home'));
				return $response;			        
			}
	    } else {
	    	$this->get('flash')->addMessage('global_error', 'Challenge NOT accepted! ' . implode(',',$v->errors()->all()) );
			$response = $response->withRedirect($this->get('router')->pathFor('challenges.home'));
			return $response;			        
	    }

	}

	if ($args['action'] == 'confirm') {

		$challengeConfirmNote = $request->getParam('challengeConfirmNote');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	        'challengeConfirmNote|Confirm note' => [$challengeConfirmNote, 'max(200)'],
	    ]);

	    if ($v->passes()) {
			$canconfirmchallenge = R::getRow( 'SELECT  *
						FROM acceptedchallenges ac 
						LEFT JOIN challenges c on c.id = ac.acceptedchallengeid 
						WHERE c.challengerid = :uid and ac.id = :cid ', 
						[
							':cid' => $args['challengeid'],
							':uid' => $app->user->id,
						]);

			if ($canconfirmchallenge) {

				$checkalreadyconfirmed = R::getRow( ' SELECT * FROM challenges c
										LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id
										WHERE challengeid = :cid and ac.confirmed = 1', 
										[
											':cid' => $args['challengeid'],
										]);

				if ($checkalreadyconfirmed) {
					$this->get('flash')->addMessage('global_error', 'Challenge ALREADY confirmed! Please refresh the page.');
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					return $response;						
				}

				$c = R::findOne('acceptedchallenges', ' id = :id ', [
					':id' => $args['challengeid'],
				]);

				if ($c) {

					$c->confirmed = 1;
					$c->confirmnote = $challengeConfirmNote;
					$c->confirmedat = Carbon::now('America/Toronto')->toDateTimeString();

				    R::begin();
				    try{
				        R::store($c);
				        R::commit();

						//send email here to challenger with confirmation

						$this->get('flash')->addMessage('global', 'Challenge confirmed!');
						$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						return $response;			        
				    }
				    catch( Exception $e ) {
				        R::rollback();
						$this->get('flash')->addMessage('global_error', 'Challenge NOT confirmed! Error: ' . $e->getMessage());
						$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						return $response;			        

				    }
						
				} else {
					$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					return $response;			        
				}

			} else {
				$response = $response->withRedirect($this->get('router')->pathFor('challenges.home'));
				return $response;			        
			}
	    } else {
	    	$this->get('flash')->addMessage('global_error', 'Challenge NOT confirmed! ' . implode(',',$v->errors()->all()) );
			$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
			return $response;			        
	    }

	}

})->setName('challenge.create.post')
  ->add($isMember)
  ->add($authenticated)
  ->add(new GenCsrf);

$app->get('/challengedetailsjson[/{challengeid}]', function($request,$response,$args) use ($app)
{
	
	if (!empty($args['challengeid'])) {

		$v = $this->get('validator');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	    ]);

		if ($v->passes()) {
			$challengedetailsjson = R::getAll( 'SELECT 
									c.id AS challengeid, 
									concat(u.first_name, \' \', u.last_name) as acceptedby,
									concat(Date(challengedate), \' (\', dayname(Date(challengedate)), \') \') AS challengedate, 
									concat(d.divisiondesc,  \' (\', d.divisionname,  \') \') AS challengeddivision,
									challengenote, 
									challengecreatedat,
									c.challenge_in_division,
									ac.acceptedbyuserid,
									ac.confirmed 				
									FROM acceptedchallenges ac 
									LEFT JOIN users u on u.id = ac.acceptedbyuserid 
									LEFT JOIN challenges c on c.id = ac.acceptedchallengeid 
									LEFT JOIN divisions d on d.id = c.challenge_in_division 
									WHERE ac.acceptedchallengeid = :cid
									', [
										':cid' => $args['challengeid'],
									]);

			echo json_encode($challengedetailsjson);	
		} else {
			echo 'failed v';
		}

	} else {

		// $challengedetailsjson = R::getAll( 'SELECT * FROM challenges' );

		// echo json_encode($challengedetailsjson);		

	}

})->setName('challengedetailsjson')
  ->add($isMember)
  ->add($authenticated);

$app->get('/challengesstatusmyjson[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{
	//$ddate = Carbon::parse($args['yyyymmdd']);
	
	if (!empty($args['yyyymmdd'])) {

		//echo $ddate;

	} else {

		$challengesstatus = R::getAll( 'SELECT 
								c.id as challengeid,
								ac.id as acceptedchallengeid,
								concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate, 									
								concat(u.first_name, \' \', u.last_name) as acceptedby,	
								d.divisiondesc as challengeddivision,
								ac.acceptednote as challengenote,
								ac.confirmed,
								ac.cancelnote,
								LENGTH(c.challengedids) - LENGTH(REPLACE(c.challengedids, \'\,\', \'\')) + 1 as numofplayers
								FROM acceptedchallenges ac 
								LEFT JOIN challenges c on c.id = ac.acceptedchallengeid 
								LEFT JOIN divisions d on d.id = c.challenge_in_division 
								LEFT JOIN users u on u.id = ac.acceptedbyuserid  
								WHERE c.challengerid = :uid 
								AND c.cancelnote IS NULL 
								',
                                [
								    ':uid' => $app->user->id,
		                        ]);

		echo json_encode($challengesstatus);		

	}

})->setName('challengesstatus.my.get.json')
  ->add($isMember)
  ->add($authenticated);