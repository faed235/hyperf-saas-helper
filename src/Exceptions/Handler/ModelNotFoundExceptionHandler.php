<?php

namespace Faed\HyperfSaasHelper\Exceptions\Handler;
use Faed\HyperfSaasHelper\Constants\Sys\HttpCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\ErrorCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;
use Faed\HyperfSaasHelper\Logs\Log;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

class ModelNotFoundExceptionHandler extends ExceptionHandler
{

    public function handle(Throwable $throwable, ResponsePlusInterface $response): ResponsePlusInterface
    {
        if ($throwable instanceof ModelNotFoundException) {
            // 阻止异常冒泡
            $this->stopPropagation();
            $trace = Log::traceTag();
            $data = json_encode(['trace'=>$trace,'error' =>'未查询到相关数据:'.$throwable->getMessage(),'code'=>ErrorCodeConstant::NOT_FOUND_ERROR],JSON_UNESCAPED_UNICODE);
            Log::get('Error',SysLogGroupConstant::HTTP)->error($data);
            return $response->withStatus(HttpCodeConstant::NOT_FOUND)->withBody(new SwooleStream($data));
        }

        // 交给下一个异常处理器
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}