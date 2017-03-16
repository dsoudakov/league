<?php

define('BR', '<br />');
define('PRE', '<pre>');
define('PREE', '</pre>');

use Tracy\Debugger;

if ($mode === '_prod') 
{
    Debugger::enable(); 
    ini_set( 'error_reporting', E_ALL );
    ini_set( 'display_errors', true );
}

use CATL\R;
use CATL\User\User;
use CATL\Auth\Authtokens;
use CATL\Validation\Validator;
use CATL\Helpers\MyCookies;
use CATL\Helpers\Hash;
use CATL\Helpers\Mail;
use CATL\Helpers\Audit;
use CATL\Helpers\Generators;
use CATL\Middleware\SessionKeeper;
use CATL\Middleware\AuthCheck;
use Carbon\Carbon;
use RandomLib\Factory as RandomLib;

// db setup
$R = new R(require_once ROOT . 'config/db.php');
//R::debug( TRUE, 2 ); //select MODE 2 to see parameters filled in
//R::fancyDebug();

$isConnected = R::testConnection();

if(!$isConnected)die("db connect failed");

require_once('RedBeanMysqlBackup.php');

$configuration = [
    'settings' => [
        'displayErrorDetails' => ($mode === '_prod' ? true : false),
    ],
    'mode' => $mode,
];

$c = new \Slim\Container($configuration);

$c['config'] = function ($c) {
    return new \Noodlehaus\Config(ROOT . 'config/app.php');
};

$c['hash'] = function($c) {
    return new Hash($c['config']);
};

$c['gen'] = function($c) {
    return new Generators();
};

$c['randomlib'] = function() {
    $factory = new RandomLib;
    return $factory->getMediumStrengthGenerator();
};

$c['validator'] = function ($c)
{ 
    return new Validator($c['config']->get('rulesMessages'));
};

$c['flash'] = function ($c) {
    return new \Slim\Flash\Messages();
};

$c['mail'] = function ($c){
    return new \Mailgun\Mailgun(
        $c['config']->get('services.mailgun.secret')
    );
};

$c['mail2'] = function ($c){
    return new Mail(
        $c['config']->get('services.mailgun.secret')
    );
};

$c['csrf'] = function ($c) {
    $guard = new \Slim\Csrf\Guard();
    $guard->setFailureCallable(function ($request, $response, $next) {
        $request = $request->withAttribute("csrf_status", false);
        return $next($request, $response);
    });
    return $guard;
};


$c['view'] = function ($c)
{
	global $app, $mode;
    $view = new \Slim\Views\Twig(ROOT . 'res/views');

    $view->addExtension(new \Slim\Views\TwigExtension(
        $c['router'],
        $c['config']->get('url.'.$mode)
    ));
	
    $view->getEnvironment()->addGlobal('gen', $c['gen']);
	$view->getEnvironment()->addGlobal('flash', $c['flash']);
    $view->getEnvironment()->addGlobal('app', $app);
    $view->getEnvironment()->addGlobal('session', $_SESSION);
    $view->getEnvironment()->addGlobal('mode', $mode);
    $view->getEnvironment()->addGlobal('SITEROOT', SITEROOT);

    $addActivefilter = new Twig_SimpleFilter('isActive', function ($uri) {
        if ($uri == $_SERVER['REQUEST_URI']) {
            return 'active';
        }
        return null;
    });

    $view->getEnvironment()->addFilter($addActivefilter);

    $addActivityCheck = new Twig_SimpleFunction('logActivity', function ($log) use ($app) {
        if ($log && $app->auth->id) {
            $ua = R::findOne('usersactive', 'user_id = :id', [
                        ':id' => $app->auth->id,
                        ]);

            if (!$ua) {
                $ua = R::dispense('usersactive');
            }
            
            $tz = 'America/Toronto';
            
            $ua->last_active = Carbon::now($tz)->toDateTimeString();
            $ua->user_id = $app->auth->id;

            $uaid = R::store($ua);
        }
        
        return null;
    });

    
    $hour_offset_mysql = '+ INTERVAL 6 HOUR'; // edt(est wit dst), will be 5 if just est, dst = false

    // if ($mode == '_dev') {
    //     $hour_offset_mysql = '';
    // }

    $addUsersOnlineCheck = new Twig_SimpleFunction('numOfUsersOnline', function () use ($app) {

        $ua = R::getRow('SELECT count(*) as count FROM usersactive WHERE last_active > (NOW() '. $hour_offset_mysql  .' - INTERVAL 15 MINUTE)');
        return $ua['count'];
    });

    $addUsersOnlineList = new Twig_SimpleFunction('listOfUsersOnline', function () use ($app) {

        $sql = 'SELECT 
                    concat(u.first_name, \' \', u.last_name, \' (\', u.email, \')\') as email 
                    FROM usersactive ua 
                    LEFT JOIN users u on u.id = ua.user_id 
                    WHERE last_active > (NOW() '. $hour_offset_mysql  .' - INTERVAL 15 MINUTE)';

        $ua = R::getAll($sql);

        foreach ($ua as $v) {
            $out .= $v['email'] . BR;
        }

        return $out;

    });

    $addUsersOnlineList2 = new Twig_SimpleFunction('listOfUsersOnline2', function () use ($app) {

        $sql = 'SELECT 
                    concat(u.first_name, \' \', u.last_name, \' (\', u.email, \')\') as email,
                    (NOW() '. $hour_offset_mysql  .' - INTERVAL 15 MINUTE) as interval1,
                    last_active 
                    FROM usersactive ua 
                    LEFT JOIN users u on u.id = ua.user_id 
                    WHERE last_active > (NOW() '. $hour_offset_mysql  .' - INTERVAL 15 MINUTE)';

        var_dump($sql);

        $ua = R::getAll($sql);

        var_dump($ua);

        foreach ($ua as $v) {
            $out .= $v['email'] . BR;
        }

        return $out;

    });

    $value_selected = new Twig_SimpleFunction('value_selected', function ($value, $field) use ($app) {
        
        if ($app->auth) {
            if ($app->auth->user->$field == $value) {
                return 'selected';
            }
        }
    });

    $rendered = new Twig_SimpleFunction('rendered', function ($value1, $value2) {
        
        if ($value1) {
            return $value1;
        }

        return $value2;
        
    });    

    $view->getEnvironment()->addFunction($value_selected);
    $view->getEnvironment()->addFunction($rendered);
    $view->getEnvironment()->addFunction($addActivityCheck);
    $view->getEnvironment()->addFunction($addUsersOnlineCheck);
    $view->getEnvironment()->addFunction($addUsersOnlineList);
    $view->getEnvironment()->addFunction($addUsersOnlineList2);

    return $view;
};

$app = new \Slim\App($c);
$app->auth = null;
$app->user = null;
$app->csrf_name = "";
$app->csrf_value = "";
$app->add(new SessionKeeper);
$app->add(new AuthCheck);
$app->add($c->get('csrf'));