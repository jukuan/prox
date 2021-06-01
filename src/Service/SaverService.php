<?php

namespace App\Service;

class SaverService
{
    private string $path;

    /**
     * @var \Exception|null
     */
    private ?\Exception $exception = null;

    /**
     * @var array
     */
    private array $domainReplacing = [];

    private static function prepareDirectory(string $dir)
    {
        $parentDir = dirname($dir);
        $upParentDir = dirname($parentDir);

        if (!file_exists($upParentDir)) {
            mkdir($upParentDir, 0755, true);
        }

        if (!file_exists($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function __construct(DotEnvService $dotEnvService)
    {
        $this->path = getHtmlCachePath();

        $this->domainReplacing = $dotEnvService->getSourceServers();

        self::prepareDirectory(dirname($this->path));
    }

    private function getPath(string $fileName): string
    {
        $path = sprintf('%s%s%s', $this->path, DIRECTORY_SEPARATOR, $fileName);

        if ($pos = strpos($path, '?')) {
            $path = substr($path, 0, $pos);
        }

        $lastCh = substr($path, -1);

        if ('/' === $lastCh) {
            $path .= 'index.html';
        }

        self::prepareDirectory(dirname($path));

        return $path;
    }

    public function saveContent($name, $content)
    {
        if ('/' === $name || empty($name)) {
            $name = 'index.html';
        }

        if (count($this->domainReplacing) > 0) {
            $content = str_replace($this->domainReplacing, '', $content);
        }

        $filePath = $this->getPath($name);

        if (file_exists($filePath) && is_file($filePath)) {
            unlink($filePath);
        }

        try {
            file_put_contents($filePath, $content);
        } catch (\Exception $exception) {
            $this->exception = $exception;
        }
    }

    public function hasError(): bool
    {
        return null !== $this->exception;
    }

    public function getError(): ?string
    {
        return null !== $this->exception ? $this->exception->getMessage() : null;
    }

    public function reset(): SaverService
    {
        $this->exception = null;
        $this->domainReplacing = [];

        return $this;
    }

    public function useReplacingFor(array $list): SaverService
    {
        $list = array_map(function ($url) {
            $url = trim($url, '"');
            $url = trim($url, '\'');

            $parsed_url = parse_url($url);
            $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
            $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';

            if (strlen($host) > 3) {
                return $scheme . rtrim($host, '/');
            }

            return null;
        }, $list);

        $list = array_unique($list);
        $this->domainReplacing = array_filter($list);

        return $this;
    }
}
