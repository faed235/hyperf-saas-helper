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

namespace Faed\HyperfSaasHelper\Exceptions\Handler;

use Faed\HyperfSaasHelper\Constants\Sys\HttpCodeConstant;
use Faed\HyperfSaasHelper\Constants\Sys\ErrorCodeConstant;
use Faed\HyperfSaasHelper\Logs\Log;
use Faed\HyperfSaasHelper\SysNotices\Wechat;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use Throwable;
use function Hyperf\Support\env;

class AppExceptionHandler extends ExceptionHandler
{
    #[Inject]
    protected RequestInterface $request;

    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response): MessageInterface|ResponseInterface
    {
        $trace = Log::traceTag();
        $route = $this->request->getUri()->getPath();

        $srt = sprintf('追踪号:[%s] %s[%s] in %s',$trace, $throwable->getMessage(), $throwable->getLine(), $throwable->getFile());
        $this->logger->error($srt);
        $this->logger->error(sprintf('追踪号:[%s] %s',$trace,$route),[
            'query'=>$this->request->getQueryParams(),
            'body' => $this->request->getParsedBody(),
        ]);
        $this->logger->error(sprintf('追踪号:[%s] %s',$trace,$throwable->getTraceAsString()));



        Wechat::sendText([
            '项目'=>env('APP_NAME'),
            '追踪号'=>$trace,
            '路由'=>$route,
            '消息'=>$srt,
        ],LogLevel::ERROR);


        $data = json_encode(['trace'=>$trace,'error' => $throwable->getMessage(),'code'=>ErrorCodeConstant::SERVER_ERROR],JSON_UNESCAPED_UNICODE);
        return $response->withStatus(HttpCodeConstant::SERVER_ERROR)->withBody(new SwooleStream($data))->withAddedHeader('content-type', 'application/json');

    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
