<?php

use CATL\R;
use CATL\Models\User;
use Carbon\Carbon;

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

$app->post('/report/{challengeid}', function($request,$response,$args) use ($app)
{

	// dump($_POST);
	// die();

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
        'challengeid' => [$args['challengeid'], 'required|int|between(-2147483648,2147483647)'],
        'matchtype| Match type' => [$matchtype, 'required|between(1,2)'],
        'winnerid|Winner' => [$winnerid, 'required|int'],
        'retired|Retired checkbox' => [$retired, 'int'],
        'retirednote|Retired note' => [$retirednote, 'max(200)'],
        '$loserscore|Loser score' => [$loserscore, 'int|0to6'],
        '$winner_1|Winner set 1 score' => [$winner_1, 'int|0to7'],
        '$winner_2|Winner set 2 score' => [$winner_2, 'int|0to7'],
        '$winner_3|Winner set 3 score' => [$winner_3, 'int|0to7'],
        '$loser_1|Loser set 1 score' => [$loser_1, 'int|0to7'],
        '$loser_2|Loser set 2 score' => [$loser_2, 'int|0to7'],
        '$loser_3|Loser set 3 score' => [$loser_3, 'int|0to7'],

    ]);

	//echo implode("<br />", $set);

    if ($v->passes()) {
		if ($matchtype == 1 && !$loserscore) { 
			
			echo '<h3><span class="label label-danger">Report failed. Number of games that loser won is not specified!</span></h3>';	
			die();
			
		} else if ($matchtype == 2 && $retired != 1 && ((!$winner_1 || !$winner_2 || !$loser_1 || !$loser_2))  ) {

			echo '<h3><span class="label label-lg label-danger">Report failed. Check your set scores. Missing numbers.</span></h3>';	
			die();
			

		}

		if ($matchtype == 2 && $retired != 1) {

			if ($winner_1 && $loser_1 && $winner_2 && $loser_2 && $winner_3 && $loser_3 ) {
				if (!User::checkSetScore($winner_1,$loser_1) ||!User::checkSetScore($winner_2,$loser_2) || !User::checkSetScore($winner_3,$loser_3)  ) {
					echo '<h3><span class="label label-lg label-danger">Set scores are invalid for 3 sets. (1)</span></h3>';	
					die();
				}

				if (!User::checkScores([$winner_1,$loser_1],[$winner_2,$loser_2],[$winner_3,$loser_3])) {
					echo '<h3><span class="label label-lg label-danger">Match score is invalid for 3 sets. (2)</span></h3>';	
					die();
				}
				
			} else {
				if ($winner_1 && $loser_1 && $winner_2 && $loser_2) {

					if (!User::checkSetScore($winner_1,$loser_1) ||!User::checkSetScore($winner_2,$loser_2)) {
						echo '<h3><span class="label label-lg label-danger">Set scores are invalid for 2 sets. (2)</span></h3>';	
						die();
					}

					if (!User::checkScores([$winner_1,$loser_1],[$winner_2,$loser_2])) {
						echo '<h3><span class="label label-lg label-danger">Match score is invalid for 2 sets.</span></h3>';	
						die();
					}
				} else {
					echo '<h3><span class="label label-lg label-danger">Set scores are missing.</span></h3>';	
					die();
				}
			}

			$loserscore = -1;

		} else if ($matchtype == 2 && $retired == 1) {
			
			if ($winner_3 && $loser_3) {
				//check 2nd and 1st
				if (!User::checkSetScore($winner_2,$loser_2)) {
					echo '<h3><span class="label label-lg label-danger">Report failed. Set 2 score is invalid.</span></h3>';	
					die();					
				}

				if (!User::checkSetScore($winner_1,$loser_1)) {
					echo '<h3><span class="label label-lg label-danger">Report failed. Set 1 score is invalid.</span></h3>';	
					die();					
				}

			} else {
				if ($winner_2 && $loser_2) {
					//check 1st
					if (!User::checkSetScore($winner_1,$loser_1)) {
						echo '<h3><span class="label label-lg label-danger">Report failed. Set 1 score is invalid here.</span></h3>';	
						die();					
					}

				} else {
					if ($winner_1 && $loser_1) {
						if ($winner_1 == $loser_1 && $winner_1 == 7) {
							echo '<h3><span class="label label-lg label-danger">Report failed. Set 1 score is invalid.</span></h3>';	
							die();					
						}
						
					} else {
						echo '<h3><span class="label label-lg label-danger">Report failed. Set 1 score is missing.</span></h3>';	
						die();					
					}

				}
			}
		}

	  	$c = R::getRow( 'SELECT *
							FROM acceptedchallenges ac 
							LEFT JOIN challenges c on c.id = ac.acceptedchallengeid 
							LEFT JOIN users u on u.id = ac.acceptedbyuserid  
							LEFT JOIN users uu on uu.id = c.challengerid
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
	  	if ($c) {
	  		$c = R::findOne('acceptedchallenges', 'id = :id', [
	  			':id' => $args['challengeid'],
	  		]);

	  		$c->winnerid = $winnerid;
	  		$c->loserscore = $loserscore;
	  		$c->matchtype = $matchtype;
	  		$c->retired = $retired;
	  		$c->retirednote = $retirednote;
			
		 if ($matchtype == 2) {
	
				$c->winner_1 = $winner_1;
				$c->winner_2 = $winner_2;
				$c->winner_3 = $winner_3;
				$c->loser_1 = $loser_1;
				$c->loser_2 = $loser_2;
				$c->loser_3 = $loser_3;

			}

	  		$r = User::storeBean($c);

	  		if ($r) {
	  			echo '<h3><span class="label label-pill label-success">Reported successfully!</span></h3>';	
	  		} else {
	  			echo '<h3><span class="label label-pill label-danger">Report failed. Please try again!</span></h3>';	
	  		}

	  		

	  	} else {

	  		echo '<h3><span class="label label-pill label-danger">Report failed. Already reported?</span></h3>';
	  	}
    }else {
    	echo '<h3><span class="label label-pill label-danger">Not reported! Check inputs</span></h3>';
    	echo '<h3><span class="label label-pill label-danger">'. $v->errors()->first() .'</span></h3>';
    }
	

})->setName('challenge.report.post')
  ->add($isMember)
  ->add($authenticated);

$app->get('/challengesreportjson', function($request,$response,$args) use ($app)
{
		$challengesreport = R::getAll( 'SELECT 
								ac.id as challengeid,
								c.id as challengeid2,
								c.challengedate,
								concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate,
								concat(u.first_name, \' \', u.last_name) as player1,
								concat(uu.first_name, \' \', uu.last_name) as player2,
								d.divisiondesc as challengeddivision
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

		echo json_encode($challengesreport);		

	

})->setName('challenges.report.get.json')
  ->add($isMember)
  ->add($authenticated);  

$app->get('/reportjson[/{challengeid}]', function($request,$response,$args) use ($app)
{
	$challengesreport = R::getAll( 'SELECT 
						ac.id as challengeid,
						c.id as challengeid2,
						c.challengedate,
						concat(Date(c.challengedate), \' (\', dayname(Date(c.challengedate)), \') \') AS challengedate,
							concat(u.first_name, \' \', u.last_name) as player1,
						u.id as player1id,
						concat(uu.first_name, \' \', uu.last_name) as player2,
						uu.id as player2id,
						d.divisiondesc as challengeddivision
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
						',
                        [
						    ':uid' => $app->user->id,
						    ':cid' => $args['challengeid'],
                        ]);

	echo json_encode($challengesreport);		

	

})->setName('challenge.report.get.json')
  ->add($isMember)
  ->add($authenticated);    