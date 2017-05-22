<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

/**
 * bootstrap.php
 *
 * Autoload classes in our app, PSR-4 style.
 * 
 *
 *
 * @package    Wdir
 * @author     James Thatcher <james@jameswt.com>
 */
define('SRC', __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR);

spl_autoload_register(function ($class) {
	$file = SRC . str_replace('\\', '/', $class) . '.php';
	if (file_exists ($file)) {
		require $file;
	}
});