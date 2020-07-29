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
//            $requestUri = '/index.html';
            $requestUri = '/';
        } else if (false !== strpos($requestUri, '?')) {
            $requestUri = strtok($requestUri, '?');
        }

        $cacheFilePath = $this->path . $requestUri;

        if (is_dir($cacheFilePath)) {
            $cacheFilePath = rtrim($cacheFilePath, '/') . '/';
            $cacheFilePath .= 'index.html';
        }

        if (file_exists($cacheFilePath)) {
            $path_parts = pathinfo($cacheFilePath);
            $extension = $path_parts['extension'];

            if ('css' === $extension) {
                header("Content-Type: text/css");
                header("X-Content-Type-Options: nosniff");
            } else if ('js' === $extension) {
                header("Content-Type: application/javascript");
                header("Cache-Control: max-age=604800, public");
            }

            echo file_get_contents($cacheFilePath);
            die();
        }


        $content = '-0-';
        $requestUri = ltrim($requestUri, '/');

        $sourceServers = [
            'http://2oreha.by.tilda.ws/',
            'https://static.tildacdn.com/',
        ];

        foreach ($sourceServers as $domain) {
            $url = $domain . $requestUri;
            $fetcher = $this->fetcher->fetch($url);

            if (!$fetcher->hasError()) {
                $content = $fetcher->getResponse();
                $this->saverService->saveContent($requestUri, $content);
                break;
            }
        }

        echo $content;
    }
}
