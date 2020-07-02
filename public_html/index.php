<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

$appDir = dirname(__DIR__);
define('APP_DIR', $appDir);
$cacheDir = $appDir . '/cache/app';

require_once $appDir . '/vendor/autoload.php';

$builder = new \DI\ContainerBuilder();
$builder->addDefinitions(APP_DIR . '/config/di-config.php');
$isProduction = false;

if ($isProduction) {
    $builder->enableCompilation($cacheDir);
    $builder->writeProxiesToFile(true, $cacheDir . '/proxies');
    $builder->ignorePhpDocErrors(true);
}

$container = $builder->build();


$container->call(function(\App\Controller\SiteController $controller) {
    $controller->index();
});
