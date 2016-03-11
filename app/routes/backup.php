<?php

//R::performMysqlBackup("backup");

use CATL\R;
use CATL\Models\User;

$app->get('/backupsql', function($request,$response,$args) use ($app)
{

	$mg = $this->get('mail');
	$c = $this->get('config');
	$domain = $c->get('services.mailgun.domain');
	$first = $c->get('services.mailgun.first');
	$last = $c->get('services.mailgun.last');
	$from = $c->get('services.mailgun.from');

	$path = realpath(dirname(__FILE__));

	echo "TASK: Perform back into Backup Folder. E-mail the result. Delete file.<br />";

	try {
		$fname = R::performMysqlBackup("backup");
	} catch (Exception $e) {
		echo $e->getMessage();
		die();
	}

	if ($fname) {
		R::gzCompressFile($path . "/../../backup/" . $fname);
		//mail the result to current user's email
		$m = $mg->MessageBuilder();
		$m->setFromAddress($from, ["first"=>$first, "last" => $last]);
		$m->addToRecipient($app->user->email, ["first" => $app->user->first_name, "last" => $app->user->last_name]);
		$m->setSubject("FHTL SQL Backup.");
		$m->setTextBody("SQL Backup completed!");
		$m->addAttachment("@" . $path . "/../../backup/" . $fname . '.gz');

		$res = $mg->post("{$domain}/messages", $m->getMessage(), $m->getFiles());

		if ($res) {

			unlink($path . "/../../backup/" . $fname);
			unlink($path . "/../../backup/" . $fname . '.gz');
			echo 'Backup success.';
		} else {

			echo 'Email failed.';
		}

	} else {
		echo 'Backup failed';
	}
    //return $this->view->render($response, 'system/backup.twig', []);

})->setName('backupsql')->add($isAdmin)->add($isMember)->add($authenticated);

$app->get('/flushsql333', function($request,$response,$args) use ($app)
{
	$c = $this->get('config');

	if ($c->get('mode') == '_dev') {

		echo 'Flusing tables.' . BR;

		$tables = [
					'challenges',
					'acceptedchallenges',
					'points',
					'auditlog',
				  ];

		foreach ($tables as $t) {
			R::exec('truncate ' . $t);
			//R::exec('ALTER TABLE ' . $t . 'AUTO_INCREMENT =1');
		}

		echo 'Done.';

	} else {

		echo 'In prod mode.';

	}
})->setName('flushsql333')->add($isAdmin)->add($isMember)->add($authenticated);