<?php

namespace CATL\Models;

use CATL\Auth\Authtokens;
use CATL\R;
use CATL\Helpers\Hash;
use CATL\Helpers\Audit;
use Carbon\Carbon;

class User
{
    public $user = null;
    public $exists = false;
    public $id;
    public $authtokens = null;
    public $remember_identifier;
    public $remember_token;
    public $hash;
    public $app = null;

    function __construct($id = null)
    {

        if ($id) {
            $user = R::findOne('users', 'email = :email', [
                ':email' => $id
            ]);

            if (!$user) {
                $user = R::findOne('users', 'id = :id', [
                    ':id' => $id
                ]);
            }

            if ($user) {
                $this->user = $user;
                $this->id = $user->id;
                $this->exists = true;
            }
        }

        if ($this->exists) {
        	$this->authtokens = new Authtokens($this->id);
        }
    }

    public static function sendToEmails($divid, $send_to_all = false, $ids_only = false)
    {

        global $app;

        $dids2 = [];

        $dids = R::getAll('select id from users where donotnotifyme = 0 and active = 1
                         and (divisionprimary = :did or divisionsecondary = :did)',
                            [':did' => $divid]
                        );

        foreach($dids as $k=>$v) {
            $dids2[] = $v['id'];
        }

        $dids_list = implode(',', $dids2);

        // if # > 0 proceed to next check
        if (count($dids) > 0 && !$send_to_all) {

            // select all challenges/acceptedchallenges played/reported/winnerid/matchtype = 1
            // in the division with challengerid or acceptedbyuserid in list of those ids

            $ccc = R::getAll('SELECT c.challengerid, ac.acceptedbyuserid
                              FROM challenges c inner join acceptedchallenges ac on c.id = ac.acceptedchallengeid
                              WHERE ((c.challengerid = :cid AND ac.acceptedbyuserid IN (' . $dids_list . '))
                              OR (c.challengerid IN  (' . $dids_list . ') AND ac.acceptedbyuserid = :cid))
                              AND winnerid is not null AND reportconfirmed is not null
                              AND challenge_in_division = :did
                              AND matchtype = 1',
                              [
                                    ':cid' => $app->auth->id,
                                    ':did' => $divid,
                              ]
                            );
            // cross check user id with acceptedbyuserid or challengerid
            // if check exists - drop that id from the list of division ids

            $excluded_ids[] = $app->auth->id;

            foreach ($ccc as $k => $v) {
                if ( $v['challengerid'] == $app->auth->id ) {
                    $excluded_ids[] = $v['acceptedbyuserid'];
                } else {
                    $excluded_ids[] = $v['challengerid'];
                }
            }

            $send_to = array_diff($dids2, $excluded_ids);

            if ($ids_only) {

                return $send_to;

            }

            $emails = self::idsToEmails($send_to);

            return $emails;

        }

        if ($send_to_all) {

                $emails = self::idsToEmails($dids2);
                return $emails;

        }
    }

    public static function idsToEmails($ids = [])
    {
            $emails2 = [];

            if (count($ids) > 0) {

                $emails = R::getAll('SELECT email
                                     FROM users
                                     WHERE donotnotifyme = 0
                                     AND active = 1
                                     AND id IN (' . implode(',', $ids) . ')'
                                    );

                foreach ($emails as $k => $v) {
                    $emails2[] = $v['email'];
                }

            }


            return $emails2;
    }

    public static function idsToNames($ids = [])
    {
            $names2 = [];

            if (!empty($ids)) {

                $sql = 'SELECT first_name, last_name
                                     FROM users
                                     WHERE donotnotifyme = 0
                                     AND active = 1
                                     AND id IN (' . implode(',', $ids) . ')';

                $names = R::getAll($sql);
                foreach ($names as $k => $v) {
                    $names2[] = $v['first_name'] . ' ' . $v['last_name'];
                }

            }

            return $names2;
    }

    public function isMember()
    {
        // check if user's email = member's email
        if ($this->exists) {
            $id = R::findOne('members',
            ' email = ? ',[ $this->user->email ]);
            if ($id) {
                return true;
            }
        }
        return false;
    }

    public function getDivisionID()
    {
        if ($this->user->divisionprimary) {
            return $this->user->divisionprimary;
        }
        return $this->user->divisionsecondary;
    }

    public function genJoinedDivisionSelect($name)
    {
        $divisions = R::findAll('divisions', ' id = :div1 or id = :div2 ', [
            ':div1' => $this->user->divisionprimary,
            ':div2' => $this->user->divisionsecondary,
        ]);

        if (empty($divisions)) {
            return null;
        }

        $ret  .= '<select id="'. $name . '" name="'. $name . '" class="form-control">';

        foreach ($divisions as $div) {
            $ret .= '<option value="' . $div->id . '">' . $div->divisionname . ' (' . $div->divisiondesc . ')' . '</option>';
        }

        $ret  .= '</select>';


        return $ret;
    }

    public function genJoinedDivisionSelect2($name)
    {
        $divisions = R::findAll('divisions', ' id = :div1 or id = :div2 ', [
            ':div1' => $this->user->divisionprimary,
            ':div2' => $this->user->divisionsecondary,
        ]);

        if (empty($divisions)) {
            return null;
        }

        $ret  .= '<select id="'. $name . '" name="'. $name . '" class="form-control">';

        foreach ($divisions as $div) {
            $ret .= '<option value="' . $div->divisiondesc . '">' . $div->divisiondesc . '</option>';
        }

        $ret  .= '</select>';


        return $ret;
    }

    public function genDivisionSelect($name, $opt = false)
    {
        $divisions = R::findAll('divisions');

        $ret  .= '<select id="'. $name . '" name="'. $name . '" class="form-control">';
            $ret .= '<option value="0">No division</option>';
        foreach ($divisions as $div) {
            $ret .= '<option value="' . $div->id . '">' . $div->divisionname . ' (' . $div->divisiondesc . ')' . '</option>';
        }

        $ret  .= '</select>';

        if ($opt == 'primary') {
            return $this->selected($ret, $this->user->divisionprimary);
        }

        if ($opt == 'secondary') {
            return $this->selected($ret, $this->user->divisionsecondary);
        }

        return $ret;
    }

    public function genDivisionSelect2($name, $opt = false)
    {
        $divisions = R::findAll('divisions');

        $ret  .= '<select id="'. $name . '" name="'. $name . '" class="form-control">';
        $ret .= '<option value="">All divisions</option>';

        foreach ($divisions as $div) {
            $ret .= '<option value="' . $div->divisiondesc . '">' . $div->divisiondesc . '</option>';
        }

        $ret  .= '</select>';

        if ($opt == 'primary') {
            return $this->selected($ret, $this->user->divisionprimary);
        }

        if ($opt == 'secondary') {
            return $this->selected($ret, $this->user->divisionsecondary);
        }

        return $ret;
    }

    public function selected($select, $value)
    {
        return str_replace(' value="' . $value . '"', ' selected value="' . $value . '"', $select);
    }

    public static function getUserFromActiveHash($hash = null)
    {
        // daf2beefc83591fd20c743edc93ba16c6f922b8d8867b1b802fccd2ef41cd52c
        if (strlen($hash) == 64) {
            $user = R::findOne('users', ' active_hash = :hash ', [
                ':hash' => $hash,
            ]);

            if ($user) {

                if ($user->active) {
                    return "Already activated!";
                } else {
                    $user->active = 1;
                    //$user->active_hash = null;
                    if (self::storeBean($user)) {
                        $_SESSION['loginemail'] = $user->email;
                        return "Account activated!";
                    }
                }
            } else {
                return null;
            }

        }
        return null;
    }

    public static function confirmReportFromActiveHash($hash = null)
    {
        global $app;

        if (strlen($hash) == 64) {

            $cc = R::findOne('acceptedchallenges', ' reportconfirmhash = :hash ', [
                ':hash' => $hash,
            ]);

            if ($cc) {

                if ($cc->reportconfirmed) {

                    return "Report is already confirmed!";

                } else {

                    $cc->reportconfirmed = 1;
                    $cc->reportconfirmedat = Carbon::now('America/Toronto')->toDateTimeString();

                    $mail = $app->getContainer()->get('mail2');

                    $emails = self::idsToEmails([$cc->reportedbyuserid]);

                    if (count($emails) > 0 ) {

                        $challenge = R::findOne('challenges', ' id = :cid ', [
                            ':cid' => $cc->acceptedchallengeid,
                        ]);

                        $challengername = self::idsToNames([$challenge->challengerid]);
                        $acceptedbyusername = self::idsToNames([$cc->acceptedbyuserid]);

                        $challengedate = Carbon::parse($challenge->challengedate);
                        $matchtype = $cc->matchtype;
                        $loserscore = $cc->loserscore;
                        $winner = $cc->winnerid;
                        $winner_1 = $cc->winner_1;
                        $winner_2 = $cc->winner_2;
                        $winner_3 = $cc->winner_3;
                        $loser_1 = $cc->loser_1;
                        $loser_2 = $cc->loser_2;
                        $loser_3 = $cc->loser_3;
                        $retired = $cc->retired;

                        if ($matchtype == 2) {

                            $body = [
                                'subject' => 'Challenge report confirmed: ' . $challengedate->toFormattedDateString()  . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
                                'title' => 'Challenge report confirmed',
                                'body' => $challengername[0] . ' vs ' . $acceptedbyusername[0] . BR .
                                'Date: ' . $challengedate->toFormattedDateString() . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
                                'Match type: Best of 3 sets' . BR .
                                'Winner: ' . self::idsToNames([$winner])[0] . BR .
                                'Score: ' . self::setScoresToStr([[$winner_1,$loser_1],[$winner_2,$loser_2],[$winner_3,$loser_3]]),
                                'signature' => 'Confirmed!',
                            ];

                        } else {

                            $body = [
                                'subject' => 'Challenge report confirmed: ' . $challengedate->toFormattedDateString()  . ' (' . $mail->days[$challengedate->dayOfWeek] . ')',
                                'title' => 'Challenge report confirmed',
                                'body' => $challengername[0] . ' vs ' . $acceptedbyusername[0] . BR .
                                'Date: ' . $challengedate->toFormattedDateString() . ' (' . $mail->days[$challengedate->dayOfWeek] . ')' . BR .
                                'Match type: Best of 7 games' . BR .
                                'Winner: ' . self::idsToNames([$winner])[0] . BR .
                                'Score: 7:' . $loserscore,
                                'signature' => 'Confirmed!',
                            ];

                        }

                        if ($retired) {

                            $body['body'] .= BR . 'Opponent retired.' . BR;

                        }

                        $mail->message($body);
                        $mail->toA($emails);

                        if (self::storeBean($cc)) {

                            $mres = $mail->send();
                            return "Report confirmed! Opponent notified.";

                        }

                    }

                    if (self::storeBean($cc)) {

                        return "Report confirmed! Opponent was not notified (DND set).";

                    }

                }

            } else {

                return false;
            }

        }

        return false;
    }

    public static function create($identifier, $password)
    {
        global $c;
        $u = R::dispense('users');

        $u->email = $identifier;
        $u->password = $c->get('hash')->password($password);
        $u->active_hash = $c->get('hash')->hash($c->get('randomlib')->generateString(128));

        $res = R::store($u);

        return new self($res);

    }

    //check password provided with password in the database
    //return true if all good
    public function checkPassword($password)
    {
        if ($this->exists) {
            if (password_verify($password, $this->user->password)) {
                return true;
            }
            return false;
        }
        return false;
    }

    public function updateRememberCredentials($identifier, $token)
    {
           return $this->update([
                'remember_identifier' => $identifier,
                'remember_token' => $token
            ]);
    }

    public function update($values = [])
    {
        if ($values && $this->exists) {
            foreach ($values as $k => $v) {
                $this->user->$k = $v;
            }

            R::begin();
            try{
                R::store( $this->user );
                R::commit();
            }
            catch( Exception $e ) {
                R::rollback();
                return false;
            }
            return true;
        }
        return null;
    }

    public function createAuthToken($app, $value = null)
    {

        if (!$value) { //create
            $rememberIdentifier = $app->randomlib->generateString(128);
            $rememberToken = $app->randomlib->generateString(128);
            $this->remember_token = $rememberToken;
            $this->remember_identifier = $rememberIdentifier;
            $rememberTokenHash = $app->hash->hash($rememberToken);
            $authtoken = R::dispense('authtokens');
            $authtoken->remember_identifier = $rememberIdentifier;
            $authtoken->remember_token = $rememberTokenHash;
            $authtoken->user_id = $this->user->id;
            $authtoken->expires = Carbon::parse('+1 week')->toDateTimeString();
            if ($this->storeBean( $authtoken )) {
                 return $authtoken;
            } else {
                return false;
            }
        }

        $token = $this->app->hash->hash($value);
        $res = authtokenExists($value);

        if ($res) {
            if ($this->app->hash->hashCheck($token, $res->remember_token)) {
                $dt = Carbon::parse($res->expires);
                $now = Carbon::now();
                if ($dt->gte($now)) { // not expired yet
                    return $res;
                } else { // token expired, delete
                    $this->deleteBean($res);
                }

            } else { //hash check fail, delete the auth token

                $this->deleteBean($res);
                return false;

            }
        } else { // no such auth token, create
                $authtoken = R::dispense('authtokens');
                $authtoken->remember_identifier = $value;
                $authtoken->remember_token = $token;
                if ($this->storeBean( $authtoken )) {
                     return $authtoken;
                } else {
                    return false;
                }
        }
    }

    public function deleteBean($bean)
    {
        R::begin();
        try {
            R::trash( $bean );
            R::commit();
        }
        catch( Exception $e ) {
            R::rollback();
            return false;
        }
        return true;
    }

    public static function storeBean($bean)
    {

        global $app;

        R::begin();

        try {

            $id = R::store( $bean );
            R::commit();

        }
        catch( Exception $e ) {

            R::rollback();

            $mail = $app->getContainer()->get('mail2');
            $mail->mailErrorToAdmin($e->getMessage());
            Audit::log($e->getMessage());

            return false;

        }

        return $id;
    }


    public function authtokenExists($value = null)
    {
        if ($value) {
            return $this->valueExists($value, 'authtokens', 'remember_identifier');
        }
        return $this->valueExists($this->remember_identifier, 'authtokens', 'remember_identifier');
    }

    public function valueExists($value, $table, $column)
    {
        $res = R::findOne($table, ' ' . $column . ' = :value', [
                ':value' => $value,
        ]);

        if ($res) {
            return $res;
        }

        return false;
    }

    public function isAdmin()
    {
        if ($this->exists) {
            return (bool) $this->user->is_admin;
        }
        return false;
    }

    public function removeRememberCredentials()
    {
        if ($this->authtokenExists()) {
            $res = R::findOne('authtokens', ' remember_identifier = :value ', [
                ':value' => $this->remember_identifier,
            ]);
            return $this->deleteBean($res);
        }
        return false;
    }

    public function expose(){
        return get_object_vars($this);
    }

    public static function checkSetScore($s1 = null, $s2 = null)
    {
        if (!$s1 || !$s2) {
            return false;
        }

        if ($s1 == $s2 || ($s1 < 0 || $s2 < 0) || ($s1 > 7 || $s2 > 7)) {
            //echo "1";
            return false;
        }

        if ($s1 > $s2) {
            if ($s1 < 6) {
                return false;
            } else {  // $s1 = 6 or 7
                if ($s1 == 7) {
                    if ($s2 < 5) {
                        //echo "2";
                        return false;
                    }
                } else { // $s1 = 6
                    if ($s2 > 4) {
                        //echo "4";
                        return false;
                    }
                }
            }
        } else {
            if ($s2 < 6) {
                return false;
            } else {  // $s2 = 6 or 7
                if ($s2 == 7) {
                    if ($s1 < 5) {
                        //echo "2";
                        return false;
                    }
                } else { // $s2 = 6
                    if ($s1 > 4) {
                        //echo "4";
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public static function checkScores($s1=null,$s2=null,$s3=null)
    {
        if (!$s1 || !$s2) {
            return false;
        }

        if (!$s3) { //check 2 sets

            if ($s1[0] < $s1[1] || $s2[0] < $s2[1]) {
                return false;
            }

        } else { // check 3 sets

            if ($s3[0] < $s3[1]) {
                return false;
            }

            if (($s1[0] > $s1[1] && $s2[0] > $s2[1]) || ($s1[0] < $s1[1] && $s2[0] < $s2[1])) {
                return false;
            }

        }

        return true;
    }

    public static function setScoresToStr($sets = [])
    {
        if ($sets) {

            if ($sets[2][0] && $sets[2][1]) {

                return $sets[0][0] . ':' . $sets[0][1] . ', ' . $sets[1][0] . ':' . $sets[1][1] . ', ' . $sets[2][0] . ':' . $sets[2][1];

            } else {

                return $sets[0][0] . ':' . $sets[0][1] . ', ' . $sets[1][0] . ':' . $sets[1][1];

            }

        }

        return 'n/a';
    }

}

