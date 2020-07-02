<?php

$appDir = dirname(__DIR__);
define('APP_DIR', $appDir);
$cacheDir = $appDir . '/cache/app';

require_once $appDir . '/vendor/autoload.php';

$builder = new \DI\ContainerBuilder();
$builder->addDefinitions('config/di-config.php');
$isProduction = false;

if ($isProduction) {
    $builder->enableCompilation($cacheDir);
    $builder->writeProxiesToFile(true, $cacheDir . '/proxies');
    $builder->ignorePhpDocErrors(true);
}

$container = $builder->build();


$container->call(function(\App\Command\RefreshCommand $command) {
    $command->run();
});
