<?php

declare(strict_types=1);

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
