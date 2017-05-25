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

require 'bootstrap.php';

$env = (object) parse_ini_file(__DIR__ . '/config/wdir.ini', true);
define('DEVELOPMENT', !empty($env->development) ? true : false);
define('ROOT', __DIR__);
define('APP_PHP', basename(__FILE__));
define('CLI', 'cli' === php_sapi_name());

// The top-most level directory. Users will not be able to traverse above this.
$cwd = getcwd();

$request = new \Wdir\Entity\Request;
$request->setCwd($cwd);

if (CLI) {
  $controller = new \Wdir\Controller\CliController;
  $request->setPath(isset($argv[1]) ? $argv[1] : "");
} 
else {
  $controller = new \Wdir\Controller\WebController;
  $request->setPath(filter_input(INPUT_GET, 'r'));
  $controller->setView(!empty($env->view) ? $env->view : 'apache');
}

$controller->setRequest($request);

if ($controller->isRequestAFile() && !CLI) {
  $controller->redirectToFile();
}
else {
  try {
    $bundle = new \Wdir\Entity\FileBundle($request);
    $controller->setBundle($bundle);
  }
  catch (\UnexpectedValueException $e) {
    $controller->setError(new \UnexpectedValueException("Invalid file or directory."));
  }

  $controller->render();
}