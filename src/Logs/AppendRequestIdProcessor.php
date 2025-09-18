<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Logs;

use Monolog\LogRecord;
use Hyperf\Context\Context;
use Monolog\Processor\ProcessorInterface;
use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;

class AppendRequestIdProcessor implements ProcessorInterface
{

    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        $record['extra']['request_id'] = Context::getOrSet(SysLogGroupConstant::REQUEST_ID, uniqid());
        return $record;
    }
}
