<?php
//define('ROOT', dirname(__DIR__).DIRECTORY_SEPARATOR);
//define('SITEROOT', dirname(dirname(($_SERVER['PHP_SELF']))));

date_default_timezone_set('America/Toronto');

define('SITEROOT', '');

session_start();

require ROOT . 'vendor/autoload.php';
require ROOT . 'app/slimcfg.php';
require ROOT . 'app/CATL/Middleware/AuthRedirects.php';

// routes
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