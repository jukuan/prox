<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$appDir = dirname(__DIR__);
defined('APP_DIR') || define('APP_DIR', $appDir);
$cacheDir = $appDir . '/cache/app';

require_once $appDir . '/vendor/autoload.php';

$container = buildContainer($cacheDir);
$container->call(function(\App\Controller\SiteController $controller) {
    $controller->index();
});
