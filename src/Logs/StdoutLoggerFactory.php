<?php

namespace Faed\HyperfSaasHelper\Logs;

use Psr\Container\ContainerInterface;
use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;

class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return Log::get(SysLogGroupConstant::SYS,SysLogGroupConstant::SYS);
    }
}