<?php

namespace CATL\Helpers;

use CATL\R;
use Carbon\Carbon;

class Audit
{
	public static function log($msg, $type = 0)
	{
		global $app;
		$a = R::dispense('auditlog');
		$a->userid = $app->auth->id;
		$a->createdat = Carbon::now('America/Toronto')->toDateTimeString();
		$a->action = $msg;
		$a->type = $type; // 0 - normal, 1 - warning, 2 - error, 3 - critical, 4 - hack attempt, 5 - unknown

		try {
			R::store($a);
			return true;
		} catch (Exception $e) {
			return false;
		}

	}

}