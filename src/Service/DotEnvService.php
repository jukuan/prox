<?php

namespace App\Service;

use Dotenv\Dotenv;

class DotEnvService
{
    private static $variables = [];

    public function __construct()
    {
        if (0 === count(self::$variables)) {
            $envFilePath = dirname(dirname(__DIR__));
            $dotEnv = Dotenv::createImmutable($envFilePath);
            self::$variables = $dotEnv->load();
            $dotEnv->required('SITE_SOURCE');
        }
    }

    public function get($key, $default = null)
    {
        return self::$variables[$key] ?? $default;
    }

    public function getSiteSource(): string
    {
        return $this->get('SITE_SOURCE');
    }
}
