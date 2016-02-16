<?php
namespace CATL\Validation;

use Violin\Violin;
use CATL\R;

class Validator extends Violin
{

    public function __construct($rules = [])
    {
        $this->addRuleMessages($rules);
    }

    // Custom rule method for checking a unique field in database.
    // Prepend custom rules with validate_
    // $args = array of args for validation function i.e. between(1,2)
    public function validate_unique($value, $input, $args)
    {

        $user = R::getAll( 
                "SELECT * FROM {$args[0]} WHERE {$args[1]} = :value",
                [':value' => trim($value)]);

        return ($user ? false : true);
    }

    public function validate_true($value, $input, $args)
    {

        return ($value == true ? true : false);
    }

    public function validate_allowedToSend($value, $input, $args)
    {

        $user = R::getAll( 
                "SELECT * FROM users WHERE email = :value",
                [':value' => trim($value) ]
        );

        return ($user ? true : false);
    }


    public function validate_arrayOfInt($arrayOfInt = null)
    {
        if ($arrayOfInt == null) {
            return false;
        }

        foreach ($arrayOfInt as $val) {
            if (gettype((int)$val) !== "integer" || (int)$val == 0) {
                return false;
            }
        }
        //die();

        return true;
    }

    public function validate_0to6($value = -1)
    {
        if ($value > 6 || $value < 0) {
            return false;
        }

        return true;
    }

    public function validate_0to7($value = -1)
    {
        if ($value > 7 || $value < 0) {
            return false;
        }
        return true;
    }

   // public function addRuleMessages2($rulesAssoc) 
   // {
   //     foreach ($rulesAssoc as $key => $value) {
   //         $this->addRuleMessage($key, $value);
   //     }
//
//
   // }
}
