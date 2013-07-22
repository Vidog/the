<?php
	use The\App\Example\Application;

	require '../The/bootstrap.php';

	$enableDebug = $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || (array_key_exists('__debug', $_COOKIE) && $_COOKIE['__debug'] == 'iddqd');

	$app = new Application('Example', 'dev', $enableDebug, dirname(__FILE__) . '/../The/');

	$app->run();

	exit;