<?php

namespace CATL\Auth;

//require_once ROOT . 'app/rb.php';
use CATL\R as R;
use Carbon\Carbon;

class Authtokens {
	// handles authtokens

	public $now;
	protected $tokens;
	public $exists = false;
	public $id;

	function __construct($id = null)
	{
		$this->now = date('Y-m-d H:i:s');
		global $app;
		if ($id) {
			$this->id = $id;
			$this->tokens = R::findAll('authtokens', ' user_id = :idd ', [
                ':idd' => $id,
            ]);

			$this->deleteExpiredTokens();

            if (count($this->tokens) > 0) {
            	$this->exists = true;
            }
            return $this;
		}
	}

	public function get($id = -1)
	{
		if ($id > -1) {
			if ($this->exists) {
				if (array_key_exists($id, $this->tokens)) {
					return $this->tokens[$id];
				}
				return false;
			}
		}

		if ($this->exists) {
			return $this->tokens;	
		}
		return false;
		
	}


	public static function deleteToken($token)
	{
		$ret = R::exec('DELETE 
						FROM authtokens 
						WHERE remember_identifier = :token ', [
	                ':token' => $token,
	            ]);
		if ($ret) {
			return true;
		}
		return false;

	}	

	public static function getHashIdFromToken($token)
	{
		$ret = R::getAll('SELECT remember_token as hash, user_id, id
						FROM authtokens 
						WHERE remember_identifier = :token 
						AND expires > :now 
						LIMIT 1 ', [
	                ':token' => $token,
	                ':now' => Carbon::now()->toDateTimeString(),
	            ]);
		if ($ret) {
			return $ret[0];
		}
		return null;

	}

	public static function getHashFromToken($token)
	{
		$ret = R::getAll('SELECT remember_token as ret
						FROM authtokens 
						WHERE remember_identifier = :token 
						AND expires > :now 
						LIMIT 1 ', [
	                ':token' => $token,
	                ':now' => Carbon::now()->toDateTimeString(),
	            ]);
		if ($ret) {
			return $ret[0]['ret'];
		}
		return null;

	}

	public static function getIdFromToken($token)
	{
		$ret = R::getAll('SELECT user_id as ret 
						FROM authtokens 
						WHERE remember_identifier = :token 
						AND expires > :now 
						LIMIT 1 ', [
	                ':token' => $token,
	                ':now' => Carbon::now()->toDateTimeString(),
	            ]);
		if ($ret) {
			return $ret[0]['ret'];
		}
		return null;		
	}

	public function addToken($user_id = null)
	{
		if (!$user_id) {
			return null;
		}

		global $c; 

		$randomlib = $c->get('randomlib');
		$hash = $c->get('hash');

		$token = $randomlib->generateString(128);

		$ret = [
			'remember_identifier' => $randomlib->generateString(128), // will be store in cookie
			'remember_token_hash' => $hash->hash($token), //will be stored in db
			'remember_token' => $token, //will be stored in cookie
		];

		$authtokens = R::dispense('authtokens');
		$authtokens->remember_identifier = $ret['remember_identifier'];
		$authtokens->remember_token = $ret['remember_token_hash'];
		$authtokens->user_id = $user_id;
		$authtokens->expires = Carbon::parse('+1 week')->toDateTimeString();
		$idstored = R::store($authtokens);

		if ($idstored) {
			return $ret;
		}
		return null;
	}

	public function countNotExpiredTokens()
	{
		if ($this->exists) {
			return (int) R::getAll('SELECT count(*) as c 
									FROM authtokens 
									WHERE expires >= :now 
									AND user_id = :id ', [
	                ':now' => $this->now,
	                ':id' => $this->id,
	            ])[0]['c'];
		}
		return 0;
	}

	protected function deleteExpiredTokens()
	{
		$c = 0;
		if (count($this->tokens) > 0) {
			$now = Carbon::now();
			foreach ($this->tokens as $token) {
				$dt = Carbon::parse($token->expires);
				if ($now->gte($dt)) {
					$id = $token->id;
					if (self::delete($token)) {
						unset($this->tokens[$id]);
						$c += 1;	
					}
				}
			}
		}
		return $c;
	}

    public static function delete($bean)
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
}	