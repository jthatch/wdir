<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

/**
 * app.php
 *
 * The public frontend to Wdir
 *
 * Further configuration found in ./config/wdir.ini.example
 * Copy to ./config/wdir.ini before going forward
 *
 *
 * @package    Wdir
 * @author     James Thatcher <james@jameswt.com>
 */
namespace Wdir;

use Wdir\Entity\FileBundle;

require 'bootstrap.php';

$env = (object) parse_ini_file(__DIR__ . '/config/wdir.ini', true);
define('DEVELOPMENT', !empty($env->development) ? true : false);
define('ROOT', __DIR__);
define('APP_PHP', basename(__FILE__));

if ('cli' === php_sapi_name()) {
  $controller = new \Wdir\Controller\CliController();
  $request = isset($argv[1]) ? $argv[1] : "src/Wdir/Entity";
} 
else {
  $controller = new \Wdir\Controller\WebController();
  $controller->setView(!empty($env->view) ? $env->view : 'apache');
  $request = filter_input(INPUT_SERVER, 'QUERY_STRING');
}

$cwd = getcwd();

$controller->setCwd($cwd);
$controller->setRequest($request);

// We get the request back santitised
$request = $controller->getRequest();

if ($controller->isRequestAFile()) {
  $controller->redirectToFile();
}
else {
  try {
    $bundle = new \Wdir\Entity\FileBundle($cwd, $request);
    $controller->setBundle($bundle);
  }
  catch (\UnexpectedValueException $e) {
    $controller->setError(new \UnexpectedValueException("Invalid file or directory."));
  }

  $controller->render();
}