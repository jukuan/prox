<?php

namespace App\Command;

use App\Service\FetcherFactory;

class RefreshCommand
{
    private $fetcherFactory;

    public function __construct(FetcherFactory $fetcherFactory)
    {
        $this->fetcherFactory = $fetcherFactory;
    }

    public function run()
    {
        $this->fetcherFactory->start();
    }
}
