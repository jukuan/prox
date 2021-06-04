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

    public function getSiteSource(): ?string
    {
        return $this->get('SITE_SOURCE');
    }

    /**
     * @return string[]
     */
    public function getSourceServers(): array
    {
        $source = $this->getSiteSource() || '';

        if (0 === strlen($source)) {
            return [];
        }

        return explode(';', $source);
    }

    /**
     * @return bool
     */
    public function hasSourceServer(): bool
    {
        $source = $this->getSiteSource() || '';

        if (strlen($source) > 0) {
            return count($this->getSourceServers()) > 0;
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getReplacedDomains(): array
    {
        return [];
    }
}
