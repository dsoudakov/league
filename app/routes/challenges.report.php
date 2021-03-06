<?php

use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;
use CATL\Helpers\Audit;

// Report a completed challenge
// only possible to do when date of challenge is at least equal to current date
// a score card is presented
// choose a winner (click on name button)
// select type of match (Best of 7 games/Best of 3 sets)
// if best of 3 sets selected ask number of sets played otherwise skip next step
// ask how many games won/lost by set (skip next 2 steps)
// put in number of games won by winner
// put in number of games won by loser
// submitting will send an email to the loser with a confirmation link to confirm the report
// once confirmed no changes are allowed without admin access and score is official

$app->get('/csrfgen', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'auth/csrf.inc', [

	]);

})->setName('gencsrf')
  ->add($isMember)
  ->add($authenticated)
  ->add(new GenCsrf);


$app->get('/report', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'challenge/challenge.report.twig', [

	]);

})->setName('challenge.report')
  ->add($isMember)
  ->add($authenticated)
  ->add(new GenCsrf);

$app->post('/confirmreport/{challengeid}', function($request,$response,$args) use ($app)
{
	$v = $this->get('validator');

	$incorrectdetails = $request->getParam('incorrectdetails');
	$correctcheck = $request->getParam('correctcheck');

	$v->validate([
        'challengeid' => [$args['challengeid'], 'required|int|between(1,2147483647)'],
        'incorrectdetails|Incorrect details' => [$incorrectdetails, 'max(100)'],
    ]);

	if ($v->passes()) {
		//already confirmed?

		//is challenge report ready to be confirmed?
		// ie is the score reported? (reportedbyuserid is not null)

		//can this user confirm?
		// ie reportedbyuserid <> app->user->id

		//mark report as confirmed

		$c = R::getAll( 'SELECT *
				FROM acceptedchallenges ac
				LEFT JOIN challenges c on c.id = ac.acceptedchallengeid
				LEFT JOIN users u on u.id = ac.acceptedbyuserid
				LEFT JOIN users uu on uu.id = c.challengerid
				LEFT JOIN divisions d on d.id = c.challenge_in_division
				WHERE (c.challengerid = :uid OR ac.acceptedbyuserid = :uid)
				AND c.cancelnote IS NULL -- not cancelled
				AND ac.cancelnote IS NULL -- not cancelled
				AND ac.confirmed = 1 -- confirmed
				AND ac.reportedbyuserid IS NOT NULL
				AND ac.reportedbyuserid <> :uid
				AND (ac.reportedbyuserid = c.challengerid OR ac.reportedbyuserid = ac.acceptedbyuserid)
				-- AND ac.reportconfirmed IS NULL
				AND ac.id = :cid
				-- AND ac.winnerid is null
				',
		        [
				    ':uid' => $app->user->id,
				    ':cid' => $args['challengeid'],
		        ]);

		if ($c) {

	  		$c = R::findOne('acceptedchallenges', 'id = :id', [
	  			':id' => $args['challengeid'],
	  		]);


	  		if (!$correctcheck) {

	  			$c->incorrectdetails = $incorrectdetails;

	  		} else {

	  			$c->incorrectdetails = null;
		  		$c->reportconfirmed = 1;
		  		$c->reportconfirmedat = Carbon::now('America/Toronto')->toDateTimeString();

	  		}

	  		$r = User::storeBean($c);

	  		if ($r) {

	  			if (!$correctcheck) {

	  				echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-warning">Reported successfully denied!</span></h3>';

	  			} else {

	  				echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-success">Reported successfully confirmed!</span></h3>';

	  			}

	  		} else {

	  			echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-danger">Report failed. Please try again!</span></h3>';

	  		}

		} else {

			echo 'Cannot confirm this report.';

		}

	} else {

		echo 'Validation failed';

	}

})->setName('challenge.confirm.report.post')
  ->add($isMember)
  ->add($authenticated);

$app->post('/report/{challengeid}', function($request,$response,$args) use ($app)
{

	$v = $this->get('validator');

	$winnerid = $request->getParam('winnerid');
	$matchtype = $request->getParam('matchtype'); // 0 - 1stto7, 1 - bo3

	$retired = $request->getParam('retired');
	$retirednote = $request->getParam('retirednote');

	$winner_1 = $request->getParam('winner_1');
	$winner_2 = $request->getParam('winner_2');
	$winner_3 = $request->getParam('winner_3');

	$loser_1 = $request->getParam('loser_1');
	$loser_2 = $request->getParam('loser_2');
	$loser_3 = $request->getParam('loser_3');

	$set = [
			$winner_1,
			$winner_2,
			$winner_3,
			$loser_1,
			$loser_2,
			$loser_3,
		   ];

	if ($matchtype == 1) {

		$loserscore = $request->getParam('loserscore');

	} else if ($matchtype == 2) {

		$loserscore = null;

	}


	$v->validate([
        'challengeid' => [$args['challengeid'], 'required|int|between(1,2147483647)'],
        'matchtype| Match type' => [$matchtype, 'required|between(1,2)'],
        'winnerid|Winner' => [$winnerid, 'required|int'],
        'retired|Retired checkbox' => [$retired, 'int'],
        'retirednote|Retired note' => [$retirednote, 'max(70)'],
        '$loserscore|Loser score' => [$loserscore, 'int|0to6'],
        '$winner_1|Winner set 1 score' => [$winner_1, 'int|0to7'],
        '$winner_2|Winner set 2 score' => [$winner_2, 'int|0to7'],
        '$winner_3|Winner set 3 score' => [$winner_3, 'int|0to7'],
        '$loser_1|Loser set 1 score' => [$loser_1, 'int|0to7'],
        '$loser_2|Loser set 2 score' => [$loser_2, 'int|0to7'],
        '$loser_3|Loser set 3 score' => [$loser_3, 'int|0to7'],

    ]);

    if ($v->passes()) {
		if ($matchtype == 1 && !$loserscore) {

			echo '<h3><span style="max-width:200px;" class="wordwrap label label-danger">Report failed. Number of games that loser won is not specified!</span></h3>';
			die();

		} else if ($matchtype == 2 && $retired != 1 && ((!$winner_1 || !$winner_2 || !$loser_1 || !$loser_2))  ) {

			echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Report failed. Check your set scores. Missing numbers.</span></h3>';
			die();


		}

		if ($matchtype == 2 && $retired != 1) {

			if ($winner_1 && $loser_1 && $winner_2 && $loser_2 && $winner_3 && $loser_3 ) {
				if (!User::checkSetScore($winner_1,$loser_1) ||!User::checkSetScore($winner_2,$loser_2) || !User::checkSetScore($winner_3,$loser_3)  ) {
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Set scores are invalid for 3 sets. (1)</span></h3>';
					die();
				}

				if (!User::checkScores([$winner_1,$loser_1],[$winner_2,$loser_2],[$winner_3,$loser_3])) {
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Match score is invalid for 3 sets. (2)</span></h3>';
					die();
				}

			} else {
				if ($winner_1 && $loser_1 && $winner_2 && $loser_2) {

					if (!User::checkSetScore($winner_1,$loser_1) ||!User::checkSetScore($winner_2,$loser_2)) {
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Set scores are invalid for 2 sets. (2)</span></h3>';
						die();
					}

					if (!User::checkScores([$winner_1,$loser_1],[$winner_2,$loser_2])) {
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Match score is invalid for 2 sets.</span></h3>';
						die();
					}
				} else {
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Set scores are missing.</span></h3>';
					die();
				}
			}

			$loserscore = -1;

		} else if ($matchtype == 2 && $retired == 1) {

			if ($winner_3 && $loser_3) {
				//check 2nd and 1st
				if (!User::checkSetScore($winner_2,$loser_2)) {
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Report failed. Set 2 score is invalid.</span></h3>';
					die();
				}

				if (!User::checkSetScore($winner_1,$loser_1)) {
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Report failed. Set 1 score is invalid.</span></h3>';
					die();
				}

			} else {
				if ($winner_2 && $loser_2) {
					//check 1st
					if (!User::checkSetScore($winner_1,$loser_1)) {
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Report failed. Set 1 score is invalid here.</span></h3>';
						die();
					}

				} else {
					if ($winner_1 && $loser_1) {
						if ($winner_1 == $loser_1 && $winner_1 == 7) {
							echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Report failed. Set 1 score is invalid.</span></h3>';
							die();
						}

					} else {
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-lg label-danger">Report failed. Set 1 score is missing.</span></h3>';
						die();
					}

				}
			}
		}

	  	$c = R::getRow( 'SELECT
	  						ac.id,
	  						c.challengerid,
	  						c.challengedate,
	  						c.challengenote,
	  						ac.acceptedbyuserid,
	  						ac.winnerid,
	  						d.id as divisionid
							FROM acceptedchallenges ac
							LEFT JOIN challenges c on c.id = ac.acceptedchallengeid
							LEFT JOIN users u on u.id = ac.acceptedbyuserid
							LEFT JOIN users uu on uu.id = c.challengerid
							LEFT JOIN divisions d on d.id = c.challenge_in_division
							WHERE (c.challengerid = :uid OR ac.acceptedbyuserid = :uid)
							AND c.cancelnote IS NULL
							AND ac.cancelnote IS NULL
							AND ac.confirmed = 1
							AND ac.id = :cid
							-- AND ac.winnerid IS NULL
							',
	                        [
							    ':uid' => $winnerid,
							    ':cid' => $args['challengeid'],
	                        ]);

	  	// dump($c);
	  	// die();

	  	if (!$c['winnerid']) {

	  		$mail = $this->get('mail2');

	  		$cc = R::findOne('acceptedchallenges', 'id = :id', [
	  			':id' => $args['challengeid'],
	  		]);

	  		$bwinner = R::dispense('points');
	  		$bloser = R::dispense('points');

	  		if ($c['challengerid'] == $winnerid) {
	  			$winner = $c['challengerid'];
	  			$loser = $c['acceptedbyuserid'];
	  		} else {
	  			$winner = $c['acceptedbyuserid'];
	  			$loser = $c['challengerid'];
	  		}

	  		$bwinner->acceptedchallengeid = $c['id'];
	  		$bwinner->playerid = $winner;
	  		$bwinner->points = 7 - $loserscore * 0.5;
	  		$bwinner->win = 1;
	  		$bwinner->loss = 0;
	  		$bwinner->divisionid =  $c['divisionid'];

	  		$bloser->acceptedchallengeid =  $c['id'];
	  		$bloser->playerid = $loser;
	  		$bloser->points = $loserscore * 0.5;
	  		$bloser->win = 0;
	  		$bloser->loss = 1;
	  		$bloser->divisionid =  $c['divisionid'];

	  		$cc->winnerid = $winnerid;
	  		$cc->loserscore = $loserscore;
	  		$cc->matchtype = $matchtype;
	  		$cc->retired = $retired;
	  		$cc->retirednote = $retirednote;
	  		$cc->reportedbyuserid = $app->user->id;
	  		$cc->reportconfirmhash = $this->get('hash')->hash($this->get('randomlib')->generateString(128));

		 if ($matchtype == 2) {

				$cc->winner_1 = $winner_1;
				$cc->winner_2 = $winner_2;
				$cc->winner_3 = $winner_3;
				$cc->loser_1 = $loser_1;
				$cc->loser_2 = $loser_2;
				$cc->loser_3 = $loser_3;

			}

	  		$res = User::storeBean($cc);
	  		$res1 = User::storeBean($bwinner);
	  		$res2 = User::storeBean($bloser);

	  		if ($res && $res1 && $res2) {

	  			$challengername = User::idsToNames([$c['challengerid']]);
	  			$acceptedbyusername = User::idsToNames([$c['acceptedbyuserid']]);

	  			if ($c['challengerid'] == $app->user->id) {

	  				$emails = User::idsToEmails([$c['acceptedbyuserid']]);

	  			} else {

					$emails = User::idsToEmails([$c['challengerid']]);

	  			}

	  			$mode = $this->get('config')->get('mode');

				if (count($emails) > 0) {

					$challengedate = Carbon::parse($c['challengedate']);

					if ($matchtype == 2) {

						$body = [
							'subject' => 'Challenge report: ' . $challengedate->toFormattedDateString()  . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
							'title' => 'Challenge report',
							'body' => $challengername[0] . ' vs ' . $acceptedbyusername[0] . BR .
							'Match type: Best of 3 sets' . BR .
							'Date: ' . $challengedate->toFormattedDateString() . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
							'Winner: ' . User::idsToNames([$winner])[0] . BR .
							'Score: ' . User::setScoresToStr([[$winner_1,$loser_1],[$winner_2,$loser_2],[$winner_3,$loser_3]]),
							'signature' => '<a class="btn btn-primary btn-lg btn-block btn-warning" href="' . $this->get('config')->get('url.' . $mode) . SITEROOT .
								$this->get('router')->pathFor('challenge.report.confirm.link', ['hash' => $cc->reportconfirmhash]) .
								'" style="box-sizing: border-box; font-size: 14px; color: #FFF; text-decoration: none; line-height: 2; font-weight: bold; text-align: center; cursor: pointer; display: inline-block; border-radius: 5px; text-transform: capitalize; background-color: #348eda; margin: 0; padding: 0; border-color: #348eda; border-style: solid; border-width: 10px 20px;">
								Confirm</a>',
						];

					} else {

						$body = [
							'subject' => 'Challenge report: ' . $challengedate->toFormattedDateString()  . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
							'title' => 'Challenge report',
							'body' => $challengername[0] . ' vs ' . $acceptedbyusername[0] . BR .
							'Date: ' . $challengedate->toFormattedDateString() . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
							'Match type: Best of 7 games' . BR .
							'Winner: ' . User::idsToNames([$winner])[0] . BR .
							'Score: 7:' . $loserscore,
							'signature' => '<a class="btn btn-primary btn-lg btn-block btn-warning" href="' . $this->get('config')->get('url.' . $mode) . SITEROOT .
								$this->get('router')->pathFor('challenge.report.confirm.link', ['hash' => $cc->reportconfirmhash]) .
								'" style="box-sizing: border-box; font-size: 14px; color: #FFF; text-decoration: none; line-height: 2; font-weight: bold; text-align: center; cursor: pointer; display: inline-block; border-radius: 5px; text-transform: capitalize; background-color: #348eda; margin: 0; padding: 0; border-color: #348eda; border-style: solid; border-width: 10px 20px;">
								Confirm</a>',
						];

					}

					if ($retired) {

						$body['body'] .= BR . 'Opponent retired.' . BR;

					}

					$mail->message($body);
					$mail->toA($emails);
					$mres = $mail->send();

					foreach ($body as $k => $v) {
						$auditlog[] = str_replace(BR, "\n", $v);
					}

					if ($mres) {

						Audit::log('Challenge reported. Mail sent, result: ' . $mres->http_response_code . ' ' . implode(' ', $auditlog));
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-success">E-mail notification sent!</span></h3>';
					} else {

						Audit::log('Mail NOT sent. ' . implode(' ', $auditlog));
						echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-danger">E-mail notification failed. Please try again!</span></h3>';

					}

				} else {

					Audit::log('Opponent has DND set in profile.');
					echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-danger">Opponent has DND set in profile. E-mail not sent.</span></h3>';

				}

	  			echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-success">Reported successfully!</span></h3>';

	  		} else {

	  			echo '<h3><spanstyle="max-width:200px;" class="wordwrap label label-pill label-danger">Report failed. Please try again!</span></h3>';

	  		}

	  	} else {

	  		echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-danger">Already reported!</span></h3>';
	  	}

    } else {

    	echo '<h3><span style="max-width:200px;" class="wordwrap  label-pill label-danger">Not reported! Check inputs</span></h3>';
    	echo '<h3><span style="max-width:200px;" class="wordwrap label label-pill label-danger">'. $v->errors()->first() .'</span></h3>';

    }

})->setName('challenge.report.post')
  ->add($isMember)
  ->add($authenticated);

$app->get('/challengesreportjson', function($request,$response,$args) use ($app)
{
		// generate json of challenges available for reporting, i.e. confirmed and not cancelled
		$challengesreport = R::getAll( 'SELECT
								ac.id as challengeid,
								c.id as challengeid2,
								c.challengedate,
								concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate,
								concat(u.first_name, \' \', u.last_name) as player1,
								concat(uu.first_name, \' \', uu.last_name) as player2,
								d.divisiondesc as challengeddivision,
								ac.winnerid,
								ac.reportedbyuserid,
								IF(ac.reportedbyuserid = :uid AND ac.reportconfirmed IS NULL,0,1) as needtoconfirm,
								ac.reportconfirmed
								FROM acceptedchallenges ac
								LEFT JOIN challenges c on c.id = ac.acceptedchallengeid
								LEFT JOIN users u on u.id = ac.acceptedbyuserid
								LEFT JOIN users uu on uu.id = c.challengerid
								LEFT JOIN divisions d on d.id = c.challenge_in_division
								WHERE (c.challengerid = :uid OR ac.acceptedbyuserid = :uid)
								AND c.cancelnote IS NULL
								AND ac.cancelnote IS NULL
								AND ac.confirmed = 1
								-- AND ac.winnerid is null
								',
                                [
								    ':uid' => $app->user->id,
		                        ]);

		$output = ['data' => $challengesreport];
		echo json_encode($output);

})->setName('challenges.report.get.json')
  ->add($isMember)
  ->add($authenticated);

$app->get('/reportjson[/{challengeid}]', function($request,$response,$args) use ($app)
{
	$sql = 'SELECT
						ac.id as challengeid,
						c.id as challengeid2,
						c.challengedate,
						concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate,
						concat(u.first_name, \' \', u.last_name) as player1,
						u.id as player1id,
						concat(uu.first_name, \' \', uu.last_name) as player2,
						uu.id as player2id,
						d.divisiondesc as challengeddivision,
						ac.winnerid,
						ac.reportedbyuserid,
						IF(ac.reportedbyuserid = :uid,0,1) as needtoconfirm,
						IF(ac.winnerid = u.id, concat(u.first_name, \' \', u.last_name),concat(uu.first_name, \' \', uu.last_name)) as winner,
						ac.matchtype,
						ac.retired,
						ac.retirednote,
						IF(ac.matchtype = 1,
							concat(\'7:\',ac.loserscore),
							IF(winner_3 IS NULL,
								concat(winner_1,\':\',loser_1,\',\',winner_2,\':\',loser_2),
								concat(winner_1,\':\',loser_1,\', \',winner_2,\':\',loser_2,\', \',winner_3,\':\',loser_3)
							)
						) AS score,
						ac.reportconfirmed
						FROM acceptedchallenges ac
						LEFT JOIN challenges c on c.id = ac.acceptedchallengeid
						LEFT JOIN users u on u.id = ac.acceptedbyuserid
						LEFT JOIN users uu on uu.id = c.challengerid
						LEFT JOIN divisions d on d.id = c.challenge_in_division
						WHERE (c.challengerid = :uid OR ac.acceptedbyuserid = :uid)
						AND c.cancelnote IS NULL
						AND ac.cancelnote IS NULL
						AND ac.confirmed = 1
						AND ac.id = :cid
						-- AND ac.winnerid is null
						';
	// echo "<pre>";
	// echo $sql;
	// echo BR;
	// echo $app->user->id . BR;
	// echo $args['challengeid'] . BR;
	// echo "</pre>";
	$challengesreport = R::getAll( $sql,
                        [
						    ':uid' => $app->user->id,
						    ':cid' => $args['challengeid'],
                        ]);

	echo json_encode($challengesreport);

})->setName('challenge.report.get.json')
  ->add($isMember)
  ->add($authenticated);

$app->get('/reportconfirm/{hash}', function($request,$response,$args) use ($app)
{
	if (!$args['hash'] || strlen($args['hash']) !== 64) {
        return $response->withRedirect($this->get('router')->pathFor('challenges.my'));
    }

    $res = User::confirmReportFromActiveHash($args['hash']);

    if (!$res) {
       return $response->withRedirect($this->get('router')->pathFor('challenges.my'));
    }

    $this->view->render($response, 'challenge/challenge.report.confirm.twig', [
        'report_status'=> $res,
    ]);

})->setName('challenge.report.confirm.link')
  ->add($isMember)
  ->add($authenticated);