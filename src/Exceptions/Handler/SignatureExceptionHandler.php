<?php

namespace Faed\HyperfSaasHelper\Exceptions\Handler;

use Faed\HyperfSaasHelper\Logs\Log;
use Faed\HyperfSaasHelper\Exceptions\SignatureException;
use Faed\HyperfSaasHelper\Constants\Sys\HttpCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\ErrorCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

class SignatureExceptionHandler extends ExceptionHandler
{

    public function handle(Throwable $throwable, ResponsePlusInterface $response): ResponsePlusInterface
    {
        if ($throwable instanceof SignatureException) {
            // 阻止异常冒泡
            $this->stopPropagation();
            $trace = Log::traceTag();
            $data = json_encode(['trace'=>$trace,'error' => $throwable->getMessage(),'code'=>ErrorCodeConstant::SIGN_ERROR],JSON_UNESCAPED_UNICODE);
            Log::get('Error',SysLogGroupConstant::HTTP)->error($data);
            return $response->withStatus(HttpCodeConstant::UNPROCESSABLE_ENTITY)->withBody(new SwooleStream($data))->withAddedHeader('content-type', 'application/json');
        }

        // 交给下一个异常处理器
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}