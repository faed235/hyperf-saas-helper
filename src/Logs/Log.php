<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Faed\HyperfSaasHelper\Logs;

use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\Logger\LoggerFactory;

class Log
{
    public static function get(string $name = 'app', string $group = 'default')
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

    public static function formatting($data): void
    {
        self::get()->info(var_export($data, true));
    }

    public static function exceptionFormat($exception,array $other = []): array
    {
        return array_merge($other,[
            'msg' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    /**
     * 日志唯一标志
     * @return mixed|string
     */
    public static function traceTag(): mixed
    {
        return Context::getOrSet(SysLogGroupConstant::REQUEST_ID, uniqid());
    }
    /**
     * 日志唯一标志
     * @return mixed|string
     */
    public static function setTraceTag($uniqid): mixed
    {
        return Context::set(SysLogGroupConstant::REQUEST_ID, $uniqid);
    }
}
