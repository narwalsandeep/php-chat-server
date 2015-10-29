<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
error_reporting(E_ALL-E_NOTICE);
ini_set('display_errors',true);
define ('APP_NAME',"Chat");
define ('APP_WEBSITE',"Chat");

ini_set('memory_limit', '1024M');

// newer php version will give warning if below timezone is not set
date_default_timezone_set('UTC');

chdir(dirname(__DIR__));

define('DOC_ROOT',dirname(__DIR__));
define("UPLOAD_DIR_NAME",'uploads');

//define("UPLOAD_DIR_PATH",DOC_ROOT.'/'.UPLOAD_DIR_NAME);
define("UPLOAD_DIR_PATH",DOC_ROOT.'/public/'.UPLOAD_DIR_NAME);

define("WWW_ROOT",'website.com');


// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Setup autoloading
require 'init_autoloader.php';


if (!defined('APPLICATION_PATH')) {
    define('APPLICATION_PATH', realpath(__DIR__ . '/../'));
}
$appConfig = include APPLICATION_PATH . '/config/application.config.php';
if (file_exists(APPLICATION_PATH . '/config/development.config.php')) {
    
    $appConfig = Zend\Stdlib\ArrayUtils::merge($appConfig, include APPLICATION_PATH . '/config/development.config.php');
}

// Run the application!
Zend\Mvc\Application::init($appConfig)->run();
