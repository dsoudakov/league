<?php

namespace CATL;

use RedBeanPHP;

class R extends RedBeanPHP\Facade {
    
    public static $started = false;

    public function __construct($config)
    {
        if (!self::$started) {
            self::$started = true;
            self::loadConfig($config);
        }
    }

    static function loadConfig($config) {

        $conn = $config['connections'][$config['default'].$config['mode']];       
        //die(var_dump($conn));
        switch($conn['driver']) {
            case 'mysql':
                self::setup ($conn['driver'] . ':host=' . $conn['host'] . '; dbname=' . $conn['database'], $conn['username'], $conn['password']);
                break;
            case 'sqlite':
                self::setup ($conn['driver'] . ':' . $conn['database']);
                break;
        }
    }

    public static function gzCompressFile($source, $level = 9){ 
        $dest = $source . '.gz'; 
        $mode = 'wb' . $level; 
        $error = false; 
        if ($fp_out = gzopen($dest, $mode)) { 
            if ($fp_in = fopen($source,'rb')) { 
                while (!feof($fp_in)) 
                    gzwrite($fp_out, fread($fp_in, 1024 * 512)); 
                fclose($fp_in); 
            } else {
                $error = true; 
            }
            gzclose($fp_out); 
        } else {
            $error = true; 
        }
        if ($error)
            return false; 
        else
            return $dest; 
    
    }

}

