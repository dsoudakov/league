<?php

namespace CATL\Models;

use CATL\Auth\Authtokens;
use CATL\R;
use CATL\Helpers\Hash;

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

    public function setApp(&$app)
    {
        $this->app = $app;
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
        R::begin();
        try {
            $id = R::store( $bean );
            R::commit();
        }
        catch( Exception $e ) {
            R::rollback();
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

        if ($s1 == 7) {
            if ($s2 < 5) {
                //echo "2";
                return false;
            }
        }

        if ($s2 == 7) {
            if ($s1 < 5) {
                //echo "3";
                return false;
            }
        }

        if ($s1 == 6) {
            if ($s2 > 4 && $s2 != 7) {
                //echo "4";
                return false;
            }
        }

        if ($s2 == 6) {
            if ($s1 > 4 && $s1 != 7) {
                //echo "5";
                return false;
            }    
        }

        return true;
    }

    public static function checkScores($s1=null,$s2=null,$s3=null)
    {
        if (!$s3) { //check 2 sets

            if ($s1[0] < $s1[1] || $s2[0] < $s2[1]) {
                return false;
            }

        } else { // check 3 sets
            $tsc = self::checkScores($s1,$s2);

            if ($tsc) {
                return false;
            }

            if ($s3[0] < $s3[1]) {
                return false;
            }

        }

        return true;
    }

}

