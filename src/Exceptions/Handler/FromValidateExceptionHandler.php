<?php

declare(strict_types=1);
/**
 * This file is part of api.
 *
 * @link     https://www.qqdeveloper.io
 * @document https://www.qqdeveloper.wiki
 * @contact  2665274677@qq.com
 * @license  Apache2.0
 */
namespace Faed\HyperfSaasHelper\Exceptions\Handler;

use Faed\HyperfSaasHelper\Constants\Sys\HttpCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\ErrorCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;
use Faed\HyperfSaasHelper\Logs\Log;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 自定义表单验证异常处理器.
 *
 * Class FromValidateExceptionHandler
 */
class FromValidateExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): MessageInterface|ResponseInterface
    {
        if ($throwable instanceof ValidationException) {
            // 阻止异常冒泡
            $this->stopPropagation();
            // 格式化异常数据格式
            $trace = Log::traceTag();
            $data = json_encode(['trace'=>$trace,'error' => $throwable->validator->errors()->first(),'code'=>ErrorCodeConstant::VERIFICATION_ERROR],JSON_UNESCAPED_UNICODE);
            Log::get('Error',SysLogGroupConstant::HTTP)->error($data);
            return $response->withStatus(HttpCodeConstant::UNPROCESSABLE_ENTITY)->withBody(new SwooleStream($data))->withAddedHeader('content-type', 'application/json');
        }

        return $response;
    }
    // 异常处理器处理该异常
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}