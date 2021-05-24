<?php

namespace App\Service;

use Dotenv\Dotenv;

class DotEnvService
{
    private static array $variables = [];

    public function __construct()
    {
        if (0 === count(self::$variables)) {
            $envFilePath = dirname(dirname(__DIR__));
            $dotEnv = Dotenv::createImmutable($envFilePath);
            self::$variables = $dotEnv->load();
            $dotEnv->required('SITE_SOURCE');
        }
    }

    public function get(string $key, $default = null)
    {
        return self::$variables[$key] ?? $default;
    }

    public function getSiteSource(): string
    {
        $source = $this->get('SITE_SOURCE');

        if (!$source) {
            throw new \DomainException('Source site is not defined');
        }

        return $source;
    }

    /**
     * @return string[]
     */
    public function getSourceServers(): array
    {
        $source = $this->getSiteSource();

        return explode(';', $source);
    }

    /**
     * @return string[]
     */
    public function getReplacedDomains(): array
    {
        return [];
    }
}
