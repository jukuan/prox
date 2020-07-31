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
            } else if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
                if ('jpg' === $extension) {
                    $extension = 'jpeg';
                }
                $type = 'image/' . $extension;
                header('Content-Type:'.$type);
                header('Content-Length: ' . filesize($cacheFilePath));
            }

            echo file_get_contents($cacheFilePath);
            die();
        }


        $content = '';
        $requestUri = ltrim($requestUri, '/');

        $sourceServers = [
            'http://2oreha.by.tilda.ws/',
            'https://static.tildacdn.com/',
        ];

        foreach ($sourceServers as $domain) {
            $content = $this->getRemoteFile($domain, $requestUri);

            if (null !== $content) {
                $this->saverService->saveContent($requestUri, $content);
                break;
            }
        }

        if ($content) {
            echo $content;
        } else {
            header('HTTP/1.0 404 Not Found');
        }
    }
}
