<?php

namespace App\Controller;

use App\Service\ContentFetcher;
use App\Service\SaverService;
use Curl\Curl;
use Symfony\Component\HttpFoundation\Request;

class SiteController
{
    private $path;
    private $request;
    private $fetcher;

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

    public function index()
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        if ('/' === $requestUri || empty($requestUri)) {
            $requestUri = '/index.html';
        }

        $cacheFilePath = $this->path . $requestUri;

        if (file_exists($cacheFilePath)) {
            echo file_get_contents($cacheFilePath);
            die();
        }


        $url = 'http://2oreha.by.tilda.ws' . $requestUri;
        $fetcher = $this->fetcher->fetch($url);

        if (!$fetcher->hasError()) {
            $content = $fetcher->getResponse();
            $this->saverService->saveContent(ltrim($requestUri, '/'), $content);
        }


        $url = 'https://static.tildacdn.com' . $requestUri;
        $fetcher = $this->fetcher->fetch($url);

        if (!$fetcher->hasError()) {
            $content = $fetcher->getResponse();
            $this->saverService->saveContent(ltrim($requestUri, '/'), $content);
        }
    }
}
