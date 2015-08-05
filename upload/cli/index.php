<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart /cli/index.php (with modififications for the Override Engine)            */
/*                                                                                    */
/*  Original file                                                                     */
/*     http://stackoverflow.com/questions/22180441/opencart-admin-cron-jobs/#24614760 */
/*  Modifications 2014 by Pete Allison                                                */
/*                                                                                    */
/*  This allows OpenCart admin pages to be accesed using the command line in the      */
/*  format php /opencart/cli/index.php route=some/route                               */
/*                                                                                    */
/* ---------------------------------------------------------------------------------- */

//setting execution time to unlimited

set_time_limit(0);

// Version
define('VERSION', '1.5.6.4');

// We need to set the directory to the root of the actual website
chdir(__DIR__ . '/../');

// Configuration
if (file_exists('admin/config.php')) {
	require_once('admin/config.php');
} else {
	cliProblem("ERROR: cli cannot access config.php");
}
$server_data = parse_url(HTTP_SERVER);

// Prevent any non-command-line requests from being made
if (php_sapi_name() != 'cli' && !defined('DEVELOPMENT') && !DEVELOPMENT) {
	cliProblem("ERROR: cli call attempted by non-cli: " . php_sapi_name());
}

// Mash the command arguments into $_GET
if (isset($argv)) {
	foreach ($argv as $arg) {
		parse_str($arg, $args);
		$_GET = array_merge($_GET, $args);
	}
	unset($_GET['index_php']);
}

// We must have a route
if (!isset($_GET['route'])) {
	cliProblem("ERROR: cli route not configured");
}

// Install
if (!defined('DIR_APPLICATION')) {
	cliProblem("ERROR: OpenCart appears to not be installed");
}

// We need to set certain items such as the server name
$server_data = parse_url(HTTP_SERVER);
putenv("SERVER_NAME=" . $server_data['host']);

/*
 * We've tested that we're being accessed using the command line, we have a
 * route set and OpenCart has been installed.  This means that we can start to
 * load up OpenCart properly.
 */

if (file_exists(__DIR__ . '/../vqmod/vqmod.php')) {
	// VirtualQMOD
	require_once(__DIR__ . '/../vqmod/vqmod.php');
	VQMod::bootup();

	// VWMODDED Startup
	require_once(VQMod::modCheck(DIR_SYSTEM . 'startup.php'));

	// Application Classes
	require_once(VQMod::modCheck(DIR_SYSTEM . 'library/currency.php'));
	require_once(VQMod::modCheck(DIR_SYSTEM . 'library/user.php'));
	require_once(VQMod::modCheck(DIR_SYSTEM . 'library/weight.php'));
	require_once(VQMod::modCheck(DIR_SYSTEM . 'library/length.php'));
} else {
	// Normal OpenCart
	// Startup
	require_once(DIR_SYSTEM . 'startup.php');

	// Application Classes
	require_once(DIR_SYSTEM . 'library/currency.php');
	require_once(DIR_SYSTEM . 'library/user.php');
	require_once(DIR_SYSTEM . 'library/weight.php');
	require_once(DIR_SYSTEM . 'library/length.php');
}

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Override Engine
if (class_exists('Factory')) {
	$factory = new Factory($registry);
	$registry->set('factory', $factory);
}

// Config
$config = !isset($factory) ? new Config() : $factory->newConfig();
$registry->set('config', $config);

// Database
$db = !isset($factory) ? new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE) : $factory->newDB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

// Settings
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");

foreach ($query->rows as $setting) {
	if (!$setting['serialized']) {
		$config->set($setting['key'], $setting['value']);
	} else {
		$config->set($setting['key'], unserialize($setting['value']));
	}
}

// Url
$url = !isset($factory) ? new Url(HTTP_SERVER, HTTPS_SERVER) : $factory->newUrl(HTTP_SERVER, $config->get('config_secure') ? HTTPS_SERVER : HTTP_SERVER);
$registry->set('url', $url);

// Log
$log = !isset($factory) ? new Log($config->get('config_error_filename')) : $factory->newLog($config->get('config_error_filename'));
$registry->set('log', $log);

function error_handler($errno, $errstr, $errfile, $errline) {
	global $log, $config;

	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			break;
		default:
			$error = 'Unknown';
			break;
	}

	if ($config->get('config_error_display')) {
		echo '<b>' . $error . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
	}

	if ($config->get('config_error_log')) {
		$log->write('PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	return true;
}

// Error Handler
set_error_handler('error_handler');

// Request
$request = !isset($factory) ? new Request() : $factory->newRequest();
$registry->set('request', $request);

// Response
$response = !isset($factory) ? new Response() : $factory->newResponse();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$registry->set('response', $response);

// Cache
$cache = !isset($factory) ? new Cache() : $factory->newCache();
$registry->set('cache', $cache);

// Session
$session = !isset($factory) ? new Session() : $factory->newSession();
$registry->set('session', $session);

// Language
$languages = array();

$query = $db->query("SELECT * FROM `" . DB_PREFIX . "language`");

foreach ($query->rows as $result) {
	$languages[$result['code']] = $result;
}

$config->set('config_language_id', $languages[$config->get('config_admin_language')]['language_id']);

// Language
$language = !isset($factory) ? new Language($languages[$config->get('config_admin_language')]['directory']) : $factory->newLanguage($languages[$config->get('config_admin_language')]['directory']);
$language->load($languages[$config->get('config_admin_language')]['filename']);
$registry->set('language', $language);

// Document
$registry->set('document', !isset($factory) ? new Document() : $factory->newDocument());

// Currency
$registry->set('currency', !isset($factory) ? new Currency($registry) : $factory->newCurrency($registry));

// Weight
$registry->set('weight', !isset($factory) ? new Weight($registry) : $factory->newWeight($registry));

// Length
$registry->set('length', !isset($factory) ? new Length($registry) : $factory->newLength($registry));

// User
$registry->set('user', !isset($factory) ? new User($registry) : $factory->newUser($registry));

//OpenBay Pro
//$registry->set('openbay', !isset($factory) ? new Openbay($registry) : $factory->newOpenbay($registry));

// Front Controller
$controller = new Front($registry);

// Don't implement the preaction for login or permission

// Router
if (!isset($factory)) {
	$action = new Action($request->get['route']);
} else {
	$action = $factory->newAction($request->get['route']);
}

// Dispatch
if (!isset($factory)) {
	$controller->dispatch($action, new Action('error/not_found'));
} else {
	$controller->dispatch($action, $factory->newAction('error/not_found'));
}

// Output
$response->output();







/**
 * This function provides access to the event log so that we can write data to
 * the log without having to fully initialise the OpenCart system.
 * @param string $log_message
 * @param integer $response_code [Optional]
 */
function cliProblem($log_message, $response_code = 400) {
	// Initialise the absolute minimum necessary for writing to the log
	if (!class_exists('VQMod')) {
		if (file_exists(__DIR__ . '/../vqmod/vqmod.php')) {
			require_once(__DIR__ . '/../vqmod/vqmod.php');
			VQMod::bootup();
			require_once(VQMod::modCheck(DIR_SYSTEM . 'startup.php'));
		} else {
			require_once(DIR_SYSTEM . 'startup.php');
		}
		$registry = new Registry();
		if (class_exists('Factory')) {
			$factory = new Factory($registry);
			$db = $factory->newDB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		} else {
			$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		}
		$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `key` = 'config_error_filename'");

		if (isset($factory)) {
			$log = $factory->newLog($query->row['value']);
		} else {
			$log = new Log($query->row['value']);
		}
	}

	echo $log_message;
	$log->write($log_message);
	if (function_exists('http_response_code')) {
		http_response_code($response_code);
	} else {
		$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
		header($protocol . ' ' . $response_code . ' ' .getResponseText($response_code));
	}

	die();
}

function getResponseText($response_code) {
	switch ($response_code) {
		case 100: $text = 'Continue'; break;
		case 101: $text = 'Switching Protocols'; break;
		case 200: $text = 'OK'; break;
		case 201: $text = 'Created'; break;
		case 202: $text = 'Accepted'; break;
		case 203: $text = 'Non-Authoritative Information'; break;
		case 204: $text = 'No Content'; break;
		case 205: $text = 'Reset Content'; break;
		case 206: $text = 'Partial Content'; break;
		case 300: $text = 'Multiple Choices'; break;
		case 301: $text = 'Moved Permanently'; break;
		case 302: $text = 'Moved Temporarily'; break;
		case 303: $text = 'See Other'; break;
		case 304: $text = 'Not Modified'; break;
		case 305: $text = 'Use Proxy'; break;
		case 400: $text = 'Bad Request'; break;
		case 401: $text = 'Unauthorized'; break;
		case 402: $text = 'Payment Required'; break;
		case 403: $text = 'Forbidden'; break;
		case 404: $text = 'Not Found'; break;
		case 405: $text = 'Method Not Allowed'; break;
		case 406: $text = 'Not Acceptable'; break;
		case 407: $text = 'Proxy Authentication Required'; break;
		case 408: $text = 'Request Time-out'; break;
		case 409: $text = 'Conflict'; break;
		case 410: $text = 'Gone'; break;
		case 411: $text = 'Length Required'; break;
		case 412: $text = 'Precondition Failed'; break;
		case 413: $text = 'Request Entity Too Large'; break;
		case 414: $text = 'Request-URI Too Large'; break;
		case 415: $text = 'Unsupported Media Type'; break;
		case 500: $text = 'Internal Server Error'; break;
		case 501: $text = 'Not Implemented'; break;
		case 502: $text = 'Bad Gateway'; break;
		case 503: $text = 'Service Unavailable'; break;
		case 504: $text = 'Gateway Time-out'; break;
		case 505: $text = 'HTTP Version not supported'; break;
		default:
			exit('Unknown http status code "' . htmlentities($code) . '"');
		break;
	}

	return $text;
}

?>
