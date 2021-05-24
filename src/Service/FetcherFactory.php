<?php

namespace App\Service;

use Exception;

class FetcherFactory
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $resources = [];

    /**
     * @var ContentFetcher
     */
    private ContentFetcher $contentFetcher;

    /**
     * @var SaverService
     */
    private SaverService $saverService;

    private static function prepareUrl(string $domain): string
    {
        $domain = trim($domain);
        $domain = rtrim($domain, '/');
        $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);

        return sprintf('http://%s', $domain);
    }

    public function __construct(
        DotEnvService $dotEnvService,
        ContentFetcher $contentFetcher,
        SaverService $saverService
    ) {
        $this->contentFetcher = $contentFetcher;
        $this->saverService = $saverService;

        $this->source = self::prepareUrl(
            $dotEnvService->getSiteSource()
        );
    }

    private function prepareSourceUrl($url): string
    {
        $url = trim($url);
        $url = trim($url, '/');
        $url = trim($url, '"');
        $url = trim($url, '\'');

        $hasDomainSchema = false !== strpos($url, 'http');

        if (!$hasDomainSchema) {
            if (false === strpos($url, $this->source)) {
                $url = trim($url, '/');
                $url = $this->source . '/' . $url;
            }
        }

        return $url;
    }

    private function migrate($url, $filename = null): ?ContentFetcher
    {
        echo sprintf('!Migrate: %s', $url) . PHP_EOL;

        if (null === $filename) {
            $partials = array_merge(['path' => ''], parse_url($url));
            $filename = $partials['path'];
        }

        if (strlen($filename) < 3) {
            return null;
        }

        $fetcher = $this->contentFetcher->fetch($url);
        $content = $fetcher->getResponse();
        $linkedSources = [];

        if ($fetcher) {
            $linkedSources = $fetcher->getLinkedSources();

            foreach ($linkedSources as $source) {
                if (!in_array($source, $this->resources)) {
                    $this->resources[] = $source;
                }
            }
        }

        $this->saverService
            ->reset()
            ->useReplacingFor($linkedSources)
            ->saveContent($filename, $content);

        return $fetcher;
    }

    public function start()
    {
        $this->migrate($this->source, 'index.html');

        while ($currentResource = current($this->resources)) {
            $url = $this->prepareSourceUrl($currentResource);
            $this->migrate($url);

            next($this->resources);
        }
    }
}
