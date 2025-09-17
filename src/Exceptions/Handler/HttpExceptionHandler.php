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

namespace Hyperf\HttpServer\Exception\Handler;

use Faed\HyperfSaasHelper\Constants\Sys\ErrorCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\HttpCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;
use Faed\HyperfSaasHelper\Logs\Log;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

class HttpExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger, protected FormatterInterface $formatter)
    {
    }

    /**
     * Handle the exception, and return the specified result.
     * @param HttpException $throwable
     */
    public function handle(Throwable $throwable, ResponsePlusInterface $response): ResponsePlusInterface
    {
        $this->stopPropagation();
        $trace = Log::traceTag();
        $data = json_encode(['trace'=>$trace,'error' => $throwable->getMessage(),'code'=>ErrorCodeConstant::PARAMETER_ERROR],JSON_UNESCAPED_UNICODE);
        Log::get('Error',SysLogGroupConstant::HTTP)->error($data);
        return $response->withStatus(HttpCodeConstant::BAD_REQUEST)->withBody(new SwooleStream($data));
    }

    /**
     * Determine if the current exception handler should handle the exception.
     *
     * @return bool If return true, then this exception handler will handle the exception,
     *              If return false, then delegate to next handler
     */
    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof HttpException;
    }
}
