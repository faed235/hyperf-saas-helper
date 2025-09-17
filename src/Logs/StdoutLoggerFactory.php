<?php

namespace Faed\HyperfSaasHelper\Logs;

use Psr\Container\ContainerInterface;

class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return Log::get('sys','sys');
    }
}