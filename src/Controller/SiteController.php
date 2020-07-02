<?php

namespace App\Controller;

use App\Service\ContentFetcher;
use App\Service\SaverService;
use Curl\Curl;
use Symfony\Component\HttpFoundation\Request;

class SiteController
{
    private string  $path;
    private Request $request;
    private ContentFetcher $fetcher;
    private SaverService $saverService;

    public function __construct(
        Request $request,
        ContentFetcher $fetcher,
        SaverService $saverService
    ) {
        $this->request = $request;
        $this->fetcher = $fetcher;
        $this->saverService = $saverService;

        $this->path = implode(DIRECTORY_SEPARATOR, [
            APP_DIR,
            'cache',
            date('Y-m-d')
        ]);
    }

    private function getRemoteFile(string $domain, string $url): ?string
    {
        $url = ltrim($url, '/');
        $url = $domain . '/' . $url;
        $content = null;
        $fetcher = $this->fetcher->fetch($url);

        if (!$fetcher->hasError()) {
            $content = $fetcher->getResponse();
        }

        return $content;
    }

    private function getCachedFile(string $filePath): ?string
    {
        $cacheFilePath = $this->path . DIRECTORY_SEPARATOR . ltrim($filePath, '/');

        if (file_exists($cacheFilePath)) {
            return file_get_contents($cacheFilePath);
        }

        return null;
    }

    public function index()
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        if ('/' === $requestUri || empty($requestUri)) {
            $requestUri = '/index.html';
        }

        $domains = [
            'http://2oreha.by.tilda.ws',
            'https://static.tildacdn.com',
        ];

        $content = $this->getCachedFile($requestUri);

        if (null === $content) {
            foreach ($domains as $domain) {
                $content = $this->getRemoteFile($domain, $requestUri);

                if (null !== $content) {
                    $requestUri = ltrim($requestUri, '/');
                    $this->saverService->saveContent($requestUri, $content);
                    break;
                }
            }
        }

        echo $content;
    }
}
