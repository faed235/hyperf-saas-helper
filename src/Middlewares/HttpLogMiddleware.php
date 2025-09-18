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

namespace Faed\HyperfSaasHelper\Middlewares;

use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;
use Faed\HyperfSaasHelper\Logs\Log;
use Faed\HyperfSaasHelper\SysNotices\Wechat;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Hyperf\Support\env;
use HyperfExtension\Jwt\JwtFactory;

class HttpLogMiddleware implements MiddlewareInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userId = $this->getUserIdFromToken($request);
        Log::get('Request', SysLogGroupConstant::HTTP)->info('请求', [
            'route' => $request->getUri()->getPath(),
            'method'=>$request->getMethod(),
            'query' => $request->getQueryParams(),
            'body' => $request->getParsedBody(),
            'user_id'=>$userId,
        ]);
        $startTime = microtime(true);

        $request = $request->withHeader('trace', '响应');
        $result = $handler->handle($request);


        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2); // 毫秒
        Log::get('Response', SysLogGroupConstant::HTTP)->info('响应', [
            'time' => $executionTime,
            'route' => $request->getUri()->getPath(),
            'status'=> $result->getStatusCode(),
            'user_id'=>$userId,
        ]);
        //执行时间大于3s
        if ($executionTime > 3){
            Wechat::sendText([
                'title'=>'执行时间过长',
                'app_name'=>env('APP_NAME'),
                'run_time' => sprintf('%.4fs', $executionTime),
                'route' => $request->getUri()->getPath(),
                'clique'=>$request->getHeaderLine('clique'),
                'version'=>$request->getHeaderLine('version'),
                'comkey'=>$request->getHeaderLine('comkey'),
                'repeat'=>$request->getHeaderLine('repeat'),
                'appid'=>$request->getHeaderLine('appid'),
                'query' => $request->getQueryParams(),
                'body' => $request->getParsedBody(),
                'user_id'=>$userId,
                'trace'=>Context::getOrSet(SysLogGroupConstant::REQUEST_ID, uniqid()),
            ]);
        }

        return $result;
    }

    protected function getUserIdFromToken(ServerRequestInterface $request): string|int|null
    {
        try {
            // 获取JWT token
            $jwt = ApplicationContext::getContainer()->get(JwtFactory::class)->make();
            $payload = $jwt->getPayload();
            // 返回sub字段
            return $payload->get('sub');

        } catch (\Exception $e) {
            // 如果解析失败，返回null或记录日志
            return null;
        }
    }

}
