<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
chdir(APP_DIR);
require APP_DIR . '/config.php';

/*
 * Connect to Database
 */
$GLOBALS['connection'] = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die ("Unable to connect: " . mysql_error());
$db = mysql_select_db(DB_NAME, $GLOBALS['connection']);

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// If you define the $opts variable then it will
// automatically be passed to the MnoSsoUser object
// for construction
// e.g:
$opts = array();
$opts['db_connection'] = $GLOBALS['connection'];


