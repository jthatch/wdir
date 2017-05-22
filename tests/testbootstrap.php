<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

/**
 * testbootstrap.php
 *
 * Autoload classes in our app, PSR-4 style. Set test
 * 
 *
 * PHP version 5.6
 *
 * @package    Wdir
 * @author     James Thatcher <james.thatcher@reporo.com>
 */
define('SRC', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR);
define('ROOT', __DIR__);
define('APP_PHP', basename(__FILE__));

$env = (object) parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '/config/wdir.ini', true);

spl_autoload_register(function ($class) {
	$file = SRC . str_replace('\\', '/', $class) . '.php';
	if (file_exists ($file)) {
		require $file;
	}
});