<?php

namespace App\Service;

use Curl\CaseInsensitiveArray;
use Curl\Curl;
use Exception;

class ContentFetcher
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $response;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Exception|null
     */
    private ?Exception $exception = null;

    public function __construct(Curl $curl)
    {
        $this->curl = $curl;
    }

    public function hasError(): bool
    {
        return null !== $this->exception;
    }

    public function fetch($url): ContentFetcher
    {
        $curl = $this->curl;
        $curl->get($url);

        if ($curl->error) {
            $this->exception = new Exception($curl->errorMessage, $curl->errorCode);
        } else {
            $this->response = $curl->response;

//            $headers = $curl->getResponseHeaders();
//            $headers = $curl->getRawResponseHeaders();

            /*if ($headers instanceof CaseInsensitiveArray) {
            }*/
        }

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    private function isIgnoredSource(string $source): bool
    {
        // TODO: use configuration
        $ignoredList = [
            'facebook.com',
            'twitter.com',
            'google.com',
            'googleapis.com',
            '//tilda.ws',
            '//tilda.cc',
        ];

        foreach ($ignoredList as $ignored) {
            if (false !== strpos($source, $ignored)) {
                return true;
            }
        }

        return false;
    }

    private function filterSources(array $list): array
    {
        return array_filter($list, function (string $resource) {
            $resource = trim($resource, '"');
            $resource = trim($resource, '\'');

            return
                !$this->isIgnoredSource($resource) &&
                strlen($resource) > 2 &&
                false === strpos($resource, ' ') &&
                '#' !== $resource[0] &&
                false === strpos($resource, 'this.') &&
                false === strpos($resource, 'data:image') &&
                false === strpos($resource, 'www.') &&
                false === strpos($resource, 'window.') &&
                false === strpos($resource, 'javascript:');
        });
    }

    public function getLinkedSources(): array
    {
        $matches = [];
        $pattern = '/(data-original|href|src)=("[^"]*")/';
        preg_match_all($pattern, $this->response, $matches);
        $list = end($matches);

        return $this->filterSources($list);
    }
}
