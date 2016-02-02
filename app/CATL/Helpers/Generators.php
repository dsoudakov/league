<?php

namespace CATL\Helpers;

use CATL\R;

class Generators {

	static function players_table_checkboxes($div = '1', $checkedids = [])
	{
		global $app;
		$players = R::findall('users', ' (divisionprimary = :div1 or divisionsecondary = :div1) and id <> :userid', 
		[
			':div1' => $div,
			':userid' => $app->user->id,
		]);

		$ret = '
		<div class="table-responsive">
		<table class="table table-condensed">
	    <thead>
	      <tr>
	        <th></th>
	        <th></th>
	        <th></th>
	      </tr>
	    </thead>
	    <tbody>
	    ';

		foreach ($players as $p) {

			if (!empty($checkedids) && in_array((string)$p->id, $checkedids)) {

				$checked = 'checked';

			} else {

				$checked = '';

			}

			$ret .= '<tr><td><input type="checkbox" ' . $checked . ' class="players" name="challengedids[]" value="' 
				 . $p->id 
				 . '" /></td><td>' 
				 . $p->first_name 
				 . ' ' 
				 . $p->last_name 
				 .'</td><td>' 
				 . $p->email 
				 . '</td></tr>';	
			
		}

		$ret .= '</tbody></table></div>';

		return $ret;
	}

	static function getDivNameByID($id = 0)
	{
		
		if (!$id) {
			return 'No division.';
		}

		$div = R::findOne('divisions', ' id = :id ', [
			':id' => $id,
		]);

		if ($div) {
			return $div->divisiondesc . ' (' . $div->divisionname . ')';
		}

		return 'No division.';

	}
}