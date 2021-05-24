<?php

declare(strict_types=1);

namespace App\Service;


class HtmlOutputTransformer
{
    private DotEnvService $dotEnvService;

    public function __construct(DotEnvService $dotEnvService)
    {
        $this->dotEnvService = $dotEnvService;
    }

    public static function isHtml(string $output): bool
    {
        return false !== strpos($output, '<body');
    }

    private function prepareDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = rtrim($domain, '/');
        $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);

        return $domain;
    }

    public function prepare(string $output): string
    {
        $replacing = [
            'http://www.%s/' => '/',
            'http://www.%s' => '',
            'http://%s/' => '/',
            'http://%s' => '',

            'https://www.%s/' => '/',
            'https://www.%s' => '',
            'https://%s/' => '/',
            'https://%s' => '',

            '/index.php/' => '/',
        ];

        foreach ($this->dotEnvService->getSourceServers() as $domain) {
            $domain = $this->prepareDomain($domain);

            foreach ($replacing as $find => $replace) {
                foreach (['href="', "href='"] as $quote) {
                    $find = $quote . sprintf($find, $domain);
                    $replace = $quote . $replace;
                    $output = str_replace($find, $replace, $output);
                }
            }
        }

        return $output;
    }
}
