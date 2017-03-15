<?php
date_default_timezone_set('America/Toronto');

if ($mode === '_prod') {

    $mode = file_get_contents('../config/mode.php');
    $siteroot = file_get_contents('../config/siteroot.php');

} else {

    $mode = file_get_contents('config/mode.php');
    $siteroot = file_get_contents('config/siteroot.php');

}

($mode === '_prod') ? define("ROOT", __DIR__.DIRECTORY_SEPARATOR . '../') : define("ROOT", __DIR__.DIRECTORY_SEPARATOR);

//($mode === '_prod') ? define('SITEROOT', $siteroot) : define('SITEROOT', '/league');
define("SITEROOT", $siteroot);

session_start();

require ROOT . 'vendor/autoload.php';
require ROOT . 'app/slimcfg.php';
require ROOT . 'app/CATL/Middleware/AuthRedirects.php';

require ROOT . 'app/routes/login.php';
require ROOT . 'app/routes/logout.php';
require ROOT . 'app/routes/home.php';
require ROOT . 'app/routes/register.php';
require ROOT . 'app/routes/activate.php';
require ROOT . 'app/routes/togglemode.php';
require ROOT . 'app/routes/password.php';
require ROOT . 'app/routes/profile.php';
require ROOT . 'app/routes/upload.php';
require ROOT . 'app/routes/players.php';
require ROOT . 'app/routes/challenges.php';
require ROOT . 'app/routes/challenges.report.php';
require ROOT . 'app/routes/members.php';
require ROOT . 'app/routes/backup.php';
require ROOT . 'app/routes/messageboard.php';
require ROOT . 'app/routes/standings.php';
require ROOT . 'app/routes/emailstests.php';
require ROOT . 'app/routes/admin.php';