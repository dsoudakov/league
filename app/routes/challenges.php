<?php

//challenges
use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;
use CATL\Helpers\Audit;

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
	$numofmatches = $request->getParam('numofmatches');
	$challengeInDivision =  $request->getParam('challengeInDivision');
	$challengedate1 = $request->getParam('challengedate');
	$challengedate = Carbon::parse($request->getParam('challengedate'));
	$today = Carbon::today('America/Toronto');
	$lastDate = Carbon::today('America/Toronto')->addWeeks(2);

	$test = Carbon::parse($challengedate)->gte($today);
	$test2 = Carbon::parse($challengedate)->lte($lastDate);

	$challengenote = $request->getParam('challengenote');

	$v = $this->get('validator');
	$mail = $this->get('mail2');

	$v->validate([
		'challengenote|Challenge note' => [$challengenote, 'required|max(100)'],
		'challengedate1|Challenge date' => [$challengedate1, 'required|max(30)'],
		'numofmatches|Number of matches' => [$numofmatches, 'required|int|between(1,5)'],
		'challengeInDivision|Challenged division' => [$challengeInDivision, 'required|between(1,99)'],
	]);

	$challengenote = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $challengenote);

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
		$c->challengecreatedat = $now->toDateTimeString();
		$c->numofmatches = $numofmatches;


		if (User::storeBean($c)) {

			$emails = User::sendToEmails($challengeInDivision);

			if (count($emails) > 0) {

				$body = [
					'subject' => 'New challenge: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
					'title' => 'New challenge',
					'body' => 'Challenger: ' . $app->user->first_name . ' ' . $app->user->last_name . BR .
					'Date: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
					'Number of matches: ' . $numofmatches . BR .
					'Note: ' . $challengenote,
					'signature' => '',
				];

				$mail->message($body);
				$mail->toA($emails);
				$mres = $mail->send();

				if ($mres) {

					Audit::log('Challenge created. Mail sent, result: ' . $mres->http_response_code . ' ' . count($emails) . ' player(s) notified.');
			    	$this->get('flash')->addMessage('global', 'Challenge created successfully. ' . count($emails) . ' player(s) notified!');

				} else {

					Audit::log('Mail NOT sent. Action = challengeall.');
			    	$this->get('flash')->addMessage('global', 'Challenge created successfully. E-mail notification failed.');

				}

				return $response->withRedirect($this->get('router')->pathFor('challenges.my'));

			} else {

				Audit::log('Nobody to notify.');
		    	$this->get('flash')->addMessage('global', 'Challenge created successfully. Nobody was notified.');
		   		return $response->withRedirect($this->get('router')->pathFor('challenges.my'));

			}


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

	$numofmatches = $request->getParam('numofmatches');
	$challengedids =  $request->getParam('challengedids');
	$challengeInDivision =  $request->getParam('challengeInDivision');
	$challengedate1 = $request->getParam('challengedate');
	$challengedate = Carbon::parse($request->getParam('challengedate'));
	$today = Carbon::today('America/Toronto');
	$lastDate = Carbon::today('America/Toronto')->addWeeks(2);

	$test = Carbon::parse($challengedate)->gte($today);
	$test2 = Carbon::parse($challengedate)->lte($lastDate);

	$challengenote = $request->getParam('challengenote');

	$v = $this->get('validator');
	$mail = $this->get('mail2');

	$v->validate([
		'challengenote|Challenge note' => [$challengenote, 'required|max(100)'],
		'challengedate1|Challenge date' => [$challengedate1, 'required'],
		'challengedids' => [$challengedids, 'required|array|arrayOfInt'],
		'challengeInDivision|Challenged division' => [$challengeInDivision, 'required|between(1,99)'],
		'numofmatches|Number of matches' => [$numofmatches, 'required|int|between(1,5)'],
	]);

	$challengenote = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $challengenote);

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
		$c->challengecreatedat = $now->toDateTimeString();
		$c->challengedids = json_encode($challengedids);
		$c->numofmatches = $numofmatches;

		if (User::storeBean($c)) {

			$emails = User::idsToEmails($challengedids);
			$names = User::idsToNames($challengedids);

			if (count($emails) > 0 ) {

				$body = [
					'subject' => 'New challenge: ' . $challengedate->toFormattedDateString() . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
					'title' => 'New challenge',
					'body' => 'Challenger: ' . $app->user->first_name . ' ' . $app->user->last_name . BR .
					'Date: ' . $challengedate->toFormattedDateString() . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
					'Number of matches: ' . $numofmatches . BR .
					'Note: ' . $challengenote . BR .
					'Notified players: ' . BR . implode(BR, $names) . BR,
					'signature' => '',
				];

				$mail->message($body);
				$mail->toA($emails);
				$mres = $mail->send();

			}

			unset($_SESSION['challengenote']);
			unset($_SESSION['challengedate']);
			unset($_SESSION['checkedids']);

			if ($mres) {

				Audit::log('Challenge created. Mail sent, result: ' . $mres->http_response_code . ' ' . count($emails) . ' player(s) notified.');
		    	$this->get('flash')->addMessage('global', 'Challenge created successfully. ' . count($emails) . ' player(s) notified!');

			} else {

				Audit::log('Mail NOT sent.');
		    	$this->get('flash')->addMessage('global', 'Challenge created successfully. E-mail notification failed.');

			}

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

$app->get('/mychallengesjson2[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{

	if (!empty($args['yyyymmdd'])) {
	} else {

		$mychallenges = R::getAll( 'SELECT
					c.id AS challengeid,
					concat(Date(challengedate), \' (\', dayname(Date(challengedate)), \') \') AS challengedate,
					d.divisiondesc AS challengeddivision,
					challengenote,
					challengecreatedat,
					count(ac.id) as numofacceptedchallenges,
					IFNULL(sum(ac.confirmed),0) as numofconfirmedchallenges,
					IFNULL(sum(ac.reportedbyuserid),0) as numofreportedchallenges,
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

		$output = ['data' => $mychallenges];
		echo json_encode($output);

	}

})->setName('challenges2.my.get.json')
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
								ac.cancelnote,
								ac.winnerid,
								ac.reportconfirmed
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

$app->get('/myacceptedchallengesjson2[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{

	if (!empty($args['yyyymmdd'])) {
	} else {

		$myacceptedchallenges = R::getAll( 'SELECT
								ac.acceptedchallengeid as challengeid,
								ac.id as acceptedchallengeid,
								concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate,
								concat(u.first_name, \' \', u.last_name) as challenger,
								d.divisiondesc as challengeddivision,
								ac.acceptednote as challengenote,
								ac.confirmed,
								ac.cancelnote as accancelnote,
								ac.winnerid,
								ac.reportconfirmed,
								ac.reportedbyuserid,
								c.cancelnote
								FROM acceptedchallenges ac
								LEFT JOIN challenges c on c.id = ac.acceptedchallengeid
								LEFT JOIN divisions d on d.id = c.challenge_in_division
								LEFT JOIN users u on u.id = c.challengerid
								WHERE ac.acceptedbyuserid = :uid
								-- AND c.cancelnote IS NULL

								', [
								':uid' => $app->user->id,
		]);

		$output = ['data' => $myacceptedchallenges];
		echo json_encode($output);

	}

})->setName('acceptedchallenges2.my.get.json')
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
										c.cancelnote as challengecancelnote,
										LENGTH(c.challengedids) - LENGTH(REPLACE(c.challengedids, \'\,\', \'\')) + 1 as numofplayers
								FROM challenges c
								LEFT JOIN users u on c.challengerid = u.id
								LEFT JOIN divisions d on c.challenge_in_division = d.id
								LEFT JOIN acceptedchallenges ac on ac.acceptedchallengeid = c.id and ac.acceptedbyuserid = :cid
								WHERE Date(challengedate) BETWEEN CURDATE() AND CURDATE() + INTERVAL 15 DAY
										and challengerid <> :cid
										and (c.challengedids like \'%"' . $app->user->id . '"%\' or c.challengedids IS NULL )
								-- AND c.cancelnote IS NULL
								',
								[
									':cid' => $app->user->id,
								]);

		echo json_encode(['data' => $issuedchallenges]);

	}
})->setName('challenges.issued.get.json')
  ->add($isMember)
  ->add($authenticated)
  ->add(new GenCsrf);

$app->get('/challenge[/{action}[/{challengeid}]]', function($request,$response,$args) use ($app)
{

	$v = $this->get('validator');

	if ($args['action'] == 'infojson') {

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(1,2147483647)'],
	    ]);

	    if ($v->passes()) {

			$challenges = R::getAll( 'SELECT

				c.id AS challengeid,
				concat(u.first_name, \' \', u.last_name) as challenger,
				concat(uu.first_name, \' \', uu.last_name) as acceptedby,
				concat(Date(challengedate), \' (\', dayname(Date(challengedate)), \') \') AS challengedate,
				concat(d.divisiondesc,  \' (\', d.divisionname,  \') \') AS challengeddivision,
				challengenote,
				challengecreatedat,
				c.challenge_in_division,
				ac.acceptedbyuserid,
				ac.acceptedat,
				ac.acceptednote,
				-- COUNT(ac.acceptedbyuserid) as acceptedbynumofplayers,
				ac.confirmed,
				ac.cancelnote as accancelnote,
				IF(ac.cancelnote IS NOT NULL, \'Opponent cancelled\', IF(ac.confirmed = 1, \'Confirmed\' ,\'Not confirmed\')) as status,
				IF(ac.reportedbyuserid IS NOT NULL, IF(ac.reportconfirmed IS NOT NULL, \'Completed\', \'Reported\') , NULL ) as reportstatus,
				c.cancelnote,
				ac.cancelnote as accancelnote,
				numofmatches,
				IFNULL(LENGTH(c.challengedids) - LENGTH(REPLACE(c.challengedids, \'\,\', \'\')) + 1,\'all\') as numofplayers,
				IF(ac.reportedbyuserid IS NOT NULL AND ac.reportconfirmed IS NOT NULL,
					IF(ac.matchtype = 1,
							concat(\'7:\',ac.loserscore),
							IF(winner_3 IS NULL,
								concat(winner_1,\':\',loser_1,\',\',winner_2,\':\',loser_2),
								concat(winner_1,\':\',loser_1,\', \',winner_2,\':\',loser_2,\', \',winner_3,\':\',loser_3)
							)
					),
				\'Not confirmed\'
				) AS score,
				IF(ac.winnerid IS NOT NULL,
					IF(ac.winnerid = u.id, concat(u.first_name, \' \', u.last_name),
						concat(uu.first_name, \' \', uu.last_name)),
				\'Not reported\'
				) as winner
				FROM acceptedchallenges ac
				LEFT JOIN challenges c on ac.acceptedchallengeid = c.id
				LEFT JOIN users u on c.challengerid = u.id
				LEFT JOIN users uu on ac.acceptedbyuserid = uu.id
				LEFT JOIN divisions d on c.challenge_in_division = d.id
				WHERE c.id = :cid ',
				[
					':cid' => $args['challengeid'],
				]);

			$matchesconfirmed = 0;

			if ($challenges) {

				foreach ($challenges as $c) {

					if (!$c['accancelnote'] && $c['confirmed'] == 1) {
						$matchesconfirmed++;
					}

				}

				echo json_encode([
									'data' => $challenges,
									'matchesconfirmed' => $matchesconfirmed,
								]);

			} else {

				echo json_encode([
									'data' => [],
									'matchesconfirmed' => 0,
								]);

			}

	    }

	}

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

				return $this->view->render($response, 'challenge/challenge.delete.partial.twig', [
					'challengeid' => $args['challengeid'],
				]);


			}

	    } else {
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

				//echo 'Beeeeeep...';
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
				return $this->view->render($response, 'challenge/challenge.confirm.twig', [
					'challengeid' => $args['challengeid'],
                    'c' => $canconfirmchallenge,
				]);
			} else {

                //echo '<h2>Can\'t confirm!</h2>';

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

	if ($args['action'] == 'details2') {

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
										c.challenge_in_division,
										numofmatches,
										cancelnote
								FROM challenges c
								LEFT JOIN users u on c.challengerid = u.id
								LEFT JOIN divisions d on c.challenge_in_division = d.id
								WHERE c.id = :cid and (c.challenge_in_division = u.divisionprimary or c.challenge_in_division = u.divisionsecondary)',
								[
									':cid' => $args['challengeid'],
								]);

			$status = 'Ready';

			if ($challenge) {

				$challenges = [];

				if ($challenge['cancelnote']) {
					$status = 'Cancelled';
				} else {
					$challenges = R::getAll('SELECT c.id
											FROM acceptedchallenges ac
											LEFT JOIN challenges c on ac.acceptedchallengeid = c.id
											WHERE c.id = :cid
											AND ac.cancelnote IS NULL
											AND ac.confirmed = 1
											',
											[
												':cid' => $args['challengeid'],
											]);
				}

				$acchallenge = [];

				$moreInfoACChallenge = $request->getParam('moreInfoACChallenge');

				if ($moreInfoACChallenge) {

						$v->validate([
					        'moreInfoACChallenge' => [$moreInfoACChallenge, 'int|between(1,2147483647)'],
					    ]);

						if ($v->passes()) {

							$acchallenge = R::getRow('SELECT
											ac.acceptednote,
											concat(u.first_name, \' \', u.last_name) as vschallenger
											FROM acceptedchallenges ac
											LEFT JOIN users u ON u.id = ac.acceptedbyuserid
											-- LEFT JOIN challenges c on ac.acceptedchallengeid = c.id
											WHERE ac.id = :cid AND ac.cancelnote IS NULL
											',
											[
												':cid' => $moreInfoACChallenge,
											]);
						}

				}

				echo json_encode([
									'data' => $challenge,
								  	'status' => $status,
								  	'numofmatchestoplay' => count($challenges),
								  	'acchallenge' => $acchallenge,
								]);

			} else {

				echo json_encode([
									'data' => [],
								  	'status' => 'NODATA',
								  	'numofmatchestoplay' => 0,
								  	'acchallenge' => [],
								]);

			}


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
	$mail = $this->get('mail2');

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
					//$this->get('flash')->addMessage('global_error', 'Challenge NOT deleted! Cannot delete confirmed challenges. Can only cancel with reasons.');
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Challenge NOT deleted! Cannot delete confirmed challenges. Can only cancel with reasons.</span></h3>';
					//$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					//return $response;
					die();
				}

		    	$emails = [];
				$challenge_accepted = R::getAll( 'select *
												  from acceptedchallenges
												  where acceptedchallengeid = :acid',
												[
													':acid' => $args['challengeid'],
												]
												);

		        if ($challenge_accepted) {

		        	foreach ($challenge_accepted as $k => $v) {
		        		$ids[] = $v['acceptedbyuserid'];
		        	}

		        	$emails = User::idsToEmails($ids);

		        }

		        R::begin();

			    try {

			        R::exec( 'DELETE FROM challenges where id = ' . $args['challengeid'] );
			        R::exec( 'DELETE FROM acceptedchallenges where acceptedchallengeid = ' . $args['challengeid'] );
			        R::commit();

			        Audit::log('Challenge deleted.');

					//$this->get('flash')->addMessage('global', 'Challenge deleted!');
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Challenge deleted!</span></h3>';

					//$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					//return $response;
			    } catch ( Exception $e ) {

			    	Audit::log('Challenge NOT deleted! Error: ' . $e->getMessage());
			    	$mail->mailErrorToAdmin('Challenge NOT deleted! Error: ' . $e->getMessage());

			        R::rollback();
					//$this->get('flash')->addMessage('global_error', 'Challenge NOT deleted! Error: ' . $e->getMessage());
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Challenge NOT deleted! Error: ' . $e->getMessage() . '</span></h3>';
					//$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					//return $response;
					die();

			    }

	        	if (count($emails) > 0) {

		        	$mail->toA($emails);

					$challengedate = Carbon::parse($challenges['challengedate']);
					$challengedate1 = $challengedate->toFormattedDateString();

					$body = [
						'subject' => 'Challenge deleted: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
						'title' => 'Challenge deleted (which you accepted)',
						'body' => 'Challenger: ' . $app->user->first_name . ' ' . $app->user->last_name . BR .
						'Date: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR,
						'signature' => '',
					];

					$mail->message($body);
					$mres = $mail->send();

					if ($mres) {

						Audit::log('Challenge deleted. Mail sent, result: ' . $mres->http_response_code . ' ' . count($emails) . ' player(s) notified.');
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">E-mail notification sent to ' . count($emails) . ' player(s).</span></h3>';

					} else {

						Audit::log('Mail NOT sent. Action = ' . $args['action'] . ', challenge: ' . $args['challengeid']);
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">E-mail notification failed!</span></h3>';
					}

	        	}
			}

	    }

	}

	if ($args['action'] == 'cancel') {

		$challengeCancelNote = $request->getParam('challengeCancelNote');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	        'challengeCancelNote| Cancel note' => [$challengeCancelNote, 'required|max(100)'],
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

						$challenge_accepted = R::getAll( 'select *
														  from acceptedchallenges
														  where acceptedchallengeid = :acid',
														[
															':acid' => $args['challengeid'],
														]
														);

				        if ($challenge_accepted) {

				        	foreach ($challenge_accepted as $k => $v) {
				        		$ids[] = $v['acceptedbyuserid'];
				        	}

				        	$emails = User::idsToEmails($ids);

				        }

				    	$c->cancelnote = $challengeCancelNote;
				    	R::store($c);
				        R::commit();
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Challenge cancelled!</span></h3>';
						//$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						//return $response;

				    } catch( Exception $e ) {

				    	Audit::log('Challenge NOT cancelled! Error: ' . $e->getMessage());
				    	$mail->mailErrorToAdmin('Challenge NOT cancelled! Error: ' . $e->getMessage());

				        R::rollback();
						//$this->get('flash')->addMessage('global_error', 'Challenge NOT cancelled! Error: ' . $e->getMessage());
						//$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						//return $response;
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Challenge NOT cancelled! Error: ' . $e->getMessage() . '</span></h3>';
						die();

				    }

		        	if (count($emails) > 0) {

			        	$mail->toA($emails);

						$challengedate = Carbon::parse($challenge['challengedate']);
						$challengedate1 = $challengedate->toFormattedDateString();

						$body = [
							'subject' => 'Challenge cancelled: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
							'title' => 'Challenge cancelled (which you accepted)',
							'body' => 'Challenger: ' . $app->user->first_name . ' ' . $app->user->last_name . BR .
							'Date: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
							'Cancel note: ' . $challengeCancelNote,
							'signature' => '',
						];

						$mail->message($body);
						$mres = $mail->send();

						if ($mres) {

							echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Players notified!</span></h3>';
							Audit::log('Challenge cancelled. Mail sent, result: ' . $mres->http_response_code . ' ' . count($emails) . ' player(s) notified.');

						} else {
							echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">E-mail notification failed!</span></h3>';
							Audit::log('Mail NOT sent. Action = ' . $args['action'] . ', challenge: ' . $args['challengeid']);

						}

		        	}
				}
			}

	    } else {
			// $this->get('flash')->addMessage('global_error', 'Challenge NOT cancelled! ' . implode(',', $v->errors()->all()));
			// $response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
			// return $response;
			echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Challenge NOT cancelled! ' . implode(',', $v->errors()->all()) . '</span></h3>';
	    }

	}

	if ($args['action'] == 'cancelaccepted') {

		$challengeCancelAcceptedNote = $request->getParam('challengeCancelAcceptedNote');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	        'challengeCancelAcceptedNote| Cancel note' => [$challengeCancelAcceptedNote, 'required|max(100)'],
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

			        if ($c) {

			        	$emails = User::idsToEmails([$challenge['challengerid']]);

					    R::begin();
					    try {

					    	$c->cancelnote = $challengeCancelAcceptedNote;
					    	R::store($c);
					        R::commit();
        					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Challenge cancelled!</span></h3>';
							//$this->get('flash')->addMessage('global', 'Challenge cancelled! Player notified.');
							//$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
							//return $response;

					    } catch( Exception $e ) {

					    	Audit::log('Challenge NOT cancelled! Error: ' . $e->getMessage());
					    	$mail->mailErrorToAdmin('Challenge NOT cancelled! Error: ' . $e->getMessage());
					        R::rollback();
							//$this->get('flash')->addMessage('global_error', 'Challenge NOT cancelled! Error: ' . $e->getMessage());
							//$response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
							//return $response;
							echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Challenge NOT cancelled! Error: ' . $e->getMessage() . '</span></h3>';
							die();
					    }

			        	if(count($emails) > 0) {

				        	$mail->toA($emails);

							$challengedate = Carbon::parse($challenge['challengedate']);
							$challengedate1 = $challengedate->toFormattedDateString();

							$body = [
								'subject' => 'Challenge cancelled by opponent: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
								'title' => 'Challenge cancelled by opponent',
								'body' => 'Opponent: ' . $app->user->first_name . ' ' . $app->user->last_name . BR .
								'Date: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
								'Cancel note: ' . $challengeCancelAcceptedNote,
								'signature' => '',
							];

							$mail->message($body);
							$mres = $mail->send();

							if ($mres) {

								Audit::log('Challenge you accepted was cancelled. Mail sent, result: ' . $mres->http_response_code . ' ' . count($emails) . ' player(s) notified.');

							} else {

								Audit::log('Mail NOT sent. Action = ' . $args['action'] . ', challenge: ' . $args['challengeid']);

							}
						}
			        }


			}

	    } else {

			// $this->get('flash')->addMessage('global_error', 'Challenge NOT cancelled! ' . implode(',', $v->errors()->all()));
			// $response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
			// return $response;
			echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Challenge NOT cancelled! ' . implode(',', $v->errors()->all()) . '</span></h3>';
	    }

	    die();
	}

	if ($args['action'] == 'accept') {

		$challengeAcceptNote = $request->getParam('challengeAcceptNote');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	        'challengeAcceptNote|Accept note' => [$challengeAcceptNote, 'required|max(100)'],
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

					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Accept failed. You already accepted.</span></h3>';
					die();

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
			    try {

			        R::store($c);
			        R::commit();

		        	$emails = User::idsToEmails([$canacceptchallenge['challengerid']]);

		        	if (count($emails) > 0) {

			        	$mail->toA($emails);

						$challengedate = Carbon::parse($canacceptchallenge['challengedate']);
						$challengedate1 = $challengedate->toFormattedDateString();

						$body = [
							'subject' => 'Challenge accepted: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
							'title' => 'Challenge accepted',
							'body' => 'Accepted by: ' . $app->user->first_name . ' ' . $app->user->last_name . BR .
							'Date: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
							'Accept note: ' . $challengeAcceptNote . BR . BR .
							'Visit league website if you wish to confirm.',
							'signature' => '',
						];

						$mail->message($body);
						$mres = $mail->send();

						if ($mres) {

							Audit::log('Challenge accepted. Mail sent, result: ' . $mres->http_response_code . ' ' . count($emails) . ' player(s) notified.');
							echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Accepted successfully! Opponent notified.</span></h3>';

						} else {

							Audit::log('Mail NOT sent. Action = ' . $args['action'] . ', challenge: ' . $args['challengeid']);
							echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Accepted successfully! E-mail notification failed.</span></h3>';
						}

					} else {

						Audit::log('Mail NOT sent. Action = ' . $args['action'] . ', challenge: ' . $args['challengeid']);
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Accepted successfully! Nobody was notified though.</span></h3>';

					}

					die();

			    } catch( Exception $e ) {

			        R::rollback();
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Accept failed. Error: ' . $e->getMessage() . '</span></h3>';
					die();

			    }

			} else {

				echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Accepted successfully!</span></h3>';
				die();

			}
	    } else {

			echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Accept failed. Error: ' . implode(',',$v->errors()->all());
			die();

	    }

	}

	if ($args['action'] == 'confirm') {

		$challengeConfirmNote = $request->getParam('challengeConfirmNote');

		$v->validate([
	        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
	        'challengeConfirmNote|Confirm note' => [$challengeConfirmNote, 'max(100)'],
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

					// $this->get('flash')->addMessage('global_error', 'Challenge ALREADY confirmed! Please refresh the page.');
					// $response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					// return $response;
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Challenge ALREADY confirmed! Please refresh the table.</span></h3>';
					die();

				}

				$c = R::findOne('acceptedchallenges', ' id = :id ', [
					':id' => $args['challengeid'],
				]);

				if ($c) {

					$c->confirmed = 1;
					$c->confirmnote = $challengeConfirmNote;
					$c->confirmedat = Carbon::now('America/Toronto')->toDateTimeString();

					$challenge = R::getRow('select * from challenges where id = ' . $c->acceptedchallengeid);

		        	$emails = User::idsToEmails([$c->acceptedbyuserid]);

				    R::begin();
				    try {

				        R::store($c);
				        R::commit();

				        Audit::log('Challenge confirmed! ac.id: ' . $c->id . ', c.id: ' . $c->acceptedchallengeid);
						// $this->get('flash')->addMessage('global', 'Challenge confirmed!');
						// $response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						// return $response;
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Challenge confirmed!</span></h3>';


				    } catch( Exception $e ) {

				        R::rollback();
				        Audit::log('Challenge NOT confirmed! Error: ' . $e->getMessage());
				        $mail->mailErrorToAdmin('Challenge NOT confirmed! ac.id: ' . $c->id . ', c.id: ' . $c->acceptedchallengeid . ' Error: ' . $e->getMessage());
						// $this->get('flash')->addMessage('global_error', 'Challenge NOT confirmed! Error: ' . $e->getMessage());
						// $response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
						// return $response;
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Challenge NOT confirmed! Error: ' . $e->getMessage() . '</span></h3>';
						die();

				    }

		        	if(count($emails) > 0) {

			        	$mail->toA($emails);

						$challengedate = Carbon::parse($challenge['challengedate']);
						$challengedate1 = $challengedate->toFormattedDateString();

						$body = [
							'subject' => 'Challenge confirmed: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
							'title' => 'Challenge confirmed',
							'body' => 'Confirmed by: ' . $app->user->first_name . ' ' . $app->user->last_name . BR .
							'Date: ' . $challengedate1 . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
							'Challenge note: ' . $challenge['challengenote'] . BR . BR .
							'Accept note: ' . $c->acceptednote . BR . BR .
							'Confirm note: ' . $challengeConfirmNote . BR,
							'signature' => '',
						];

						$mail->message($body);
						$mres = $mail->send();

						if ($mres) {

							Audit::log('Challenge confirmed. Mail sent, result: ' . $mres->http_response_code . ' ' . count($emails) . ' player(s) notified.');
							echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-success">Opponent was notified!</span></h3>';

						} else {

							Audit::log('Mail NOT sent. Action = ' . $args['action'] . ', challenge: ' . $args['challengeid']);
							echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Notification failed!</span></h3>';

						}

					} else {

						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Opponent was NOT notified!</span></h3>';
					}

				} else {

					// $response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
					// return $response;

				}

			} else {

				// $response = $response->withRedirect($this->get('router')->pathFor('challenges.home'));
				// return $response;

			}

	    } else {

	  		// $this->get('flash')->addMessage('global_error', 'Challenge NOT confirmed! ' . implode(',',$v->errors()->all()) );
			// $response = $response->withRedirect($this->get('router')->pathFor('challenges.my'));
			// return $response;
			echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Challenge NOT confirmed! ' . implode(',',$v->errors()->all()) . '</span></h3>';

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

$app->get('/challengesstatusmyjson2[/{yyyymmdd}]', function($request,$response,$args) use ($app)
{

	if (!empty($args['yyyymmdd'])) {
	} else {

		$challengesstatus = R::getAll( 'SELECT
								ac.id as acceptedchallengeid,
								c.id as challengeid,
								concat(u.first_name, \' \', u.last_name) as acceptedby,
								concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate,
								d.divisiondesc as challengeddivision,
								LENGTH(c.challengedids) - LENGTH(REPLACE(c.challengedids, \'\,\', \'\')) + 1 as numofplayers,
								ac.acceptednote as challengenote,
								ac.confirmed,
								c.cancelnote,
								ac.cancelnote as accancelnote
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

		//echo json_encode($challengesstatus);
		$output = ['data' => $challengesstatus];
		echo json_encode($output);

	}

})->setName('challengesstatus2.my.get.json')
  ->add($isMember)
  ->add($authenticated);