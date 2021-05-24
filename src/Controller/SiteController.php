<?php

namespace App\Controller;

use App\Service\ContentFetcher;
use App\Service\HtmlOutputTransformer;
use App\Service\DotEnvService;
use App\Service\SaverService;
use Curl\Curl;
use Symfony\Component\HttpFoundation\Request;

class SiteController
{
    private string  $path;
    private Request $request;
    private ContentFetcher $fetcher;
    private SaverService $saverService;
    private DotEnvService $dotEnvService;
    private HtmlOutputTransformer $htmlOutput;

    public function __construct(
        DotEnvService $dotEnvService,
        Request $request,
        ContentFetcher $fetcher,
        SaverService $saverService,
        HtmlOutputTransformer $htmlOutputTransformer
    ) {
        $this->request = $request;
        $this->fetcher = $fetcher;
        $this->saverService = $saverService;
        $this->dotEnvService = $dotEnvService;
        $this->htmlOutput = $htmlOutputTransformer;

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
            $this->handleFileTypeExtension($cacheFilePath);
            $output = file_get_contents($cacheFilePath);

            if ($this->htmlOutput::isHtml($output)) {
                $output = $this->htmlOutput->prepare($output);
            }

            echo $output;
            die();
        }


        $content = '';
        $requestUri = ltrim($requestUri, '/');

        foreach ($this->dotEnvService->getSourceServers() as $domain) {
            $content = $this->getRemoteFile($domain, $requestUri);

            if (null !== $content) {
                $this->saverService->saveContent($requestUri, $content);
                break;
            }
        }

        if ($content) {
            $this->handleFileTypeExtension($cacheFilePath);

            if ($this->htmlOutput::isHtml($content)) {
                $content = $this->htmlOutput->prepare($content);
            }

            echo $content;
        } else {
            header('HTTP/1.0 404 Not Found');
        }
    }

    private function handleFileTypeExtension(string $filePath): void
    {
        $path_parts = pathinfo($filePath);
        $extension = $path_parts['extension'];

        if ('css' === $extension) {
            header("Content-Type: text/css");
            header("X-Content-Type-Options: nosniff");
        } else if ('js' === $extension) {
            header("Content-Type: application/javascript");
            header("Cache-Control: max-age=604800, public");
        } else if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'])) {
            if ('jpg' === $extension) {
                $extension = 'jpeg';
            }
            $type = 'image/' . $extension;
            header('Content-Type:'.$type);
            header('Content-Length: ' . filesize($filePath));
        } else if (in_array($extension, ['html', 'html'], true)) {
            header("Content-Type: text/html");
        }
    }
}
