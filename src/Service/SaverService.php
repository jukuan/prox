<?php

namespace App\Service;

class SaverService
{
    private $path;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var array
     */
    private $domainReplacings = [];

    private static function prepareDirectory(string $dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function __construct()
    {
        $this->path = implode(DIRECTORY_SEPARATOR, [
            APP_DIR,
            'cache',
            date('Y-m-d')
        ]);

        self::prepareDirectory(dirname($this->path));
    }

    private function getPath(string $fileName): string
    {
        $path = sprintf('%s%s%s', $this->path, DIRECTORY_SEPARATOR, $fileName);

        self::prepareDirectory(dirname($path));

        if ($pos = strpos($path, '?')) {
            $path = substr($path, 0, $pos);
        }

        return $path;
    }

    public function saveContent($name, $content)
    {
        if (count($this->domainReplacings) > 0) {
            $content = str_replace($this->domainReplacings, '', $content);
        }

        try {
            file_put_contents($this->getPath($name), $content);
        } catch (\Exception $exception) {
            $this->exception = $exception;
        }
    }

    public function reset()
    {
        $this->exception = null;
        $this->domainReplacings = [];

        return $this;
    }

    public function useReplacingFor(array $list)
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
        $this->domainReplacings = array_filter($list);

        return $this;
    }
}
