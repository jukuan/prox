<?php

declare(strict_types=1);

if (file_exists(APP_DIR . '/config/di-config.php')) {
    require_once APP_DIR . '/config/config-local.php';
}

defined('APP_DIR') || define('APP_DIR', dirname(__DIR__));

if ( ! function_exists('dd')) {
    function dd($expression, $comment = null)
    {
        if ($comment) {
            var_dump($comment);
        }

        var_dump($expression);
        die();
    }
}

function buildContainer(string $cacheDir): \DI\Container
{
    $builder = new \DI\ContainerBuilder();
    $builder->addDefinitions(APP_DIR . '/config/di-config.php');
    $isProduction = false;

    if ($isProduction) {
        $builder->enableCompilation($cacheDir);
        $builder->writeProxiesToFile(true, $cacheDir . '/proxies');
        $builder->ignorePhpDocErrors(true);
    }

    return $builder->build();
}

function getHtmlCachePath(): string
{
    $appDir = defined('APP_DIR') ? APP_DIR : dirname(__DIR__);
    $path = (new \App\Service\DotEnvService())->get('HTML_CACHE_PATH');

    if (!isset($path[0])) {
        return implode(DIRECTORY_SEPARATOR, [
            $appDir,
            'cache',
            date('Y-m-d')
        ]);
    }

    if ('/' === $path[0] || '~' === $path[0]) {
        return $path;
    }

    if ('.' === $path[0]) {
        return $appDir . DIRECTORY_SEPARATOR . $path;
    }

    return $appDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $path;
}
