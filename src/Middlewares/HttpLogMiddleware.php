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

use Exception;
use Faed\HyperfSaasHelper\Constants\Sys\SysLogGroupConstant;
use Faed\HyperfSaasHelper\Logs\Log;
use Faed\HyperfSaasHelper\SysNotices\Wechat;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use HyperfExtension\Jwt\JwtFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Hyperf\Config\config;
use function Hyperf\Support\env;

class HttpLogMiddleware implements MiddlewareInterface
{
    protected const SLOW_REQUEST_THRESHOLD = 3.0; // 慢请求阈值（秒）

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);
        $userId = $this->getUserIdFromToken($request);

        $additionalData = $this->getAdditionalRequestData($request); // 获取额外参数


        $this->logRequest($request, $userId,$additionalData);

        $response = $handler->handle($request);

        $executionTime = $this->calculateExecutionTime($startTime);
        $this->logResponse($request, $response, $executionTime, $userId,$additionalData);
        $this->handleSlowRequest($request, $executionTime, $userId);

        return $response;
    }

    protected function logRequest(ServerRequestInterface $request, $userId,array $additionalData = []): void
    {
        Log::get('请求', SysLogGroupConstant::HTTP)->info('请求', array_merge($additionalData,[
            'route' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'query' => $request->getQueryParams(),
            'body' => $request->getParsedBody(),
            'user_id' => $userId,
        ]));
    }

    protected function logResponse(ServerRequestInterface $request, ResponseInterface $response, float $executionTime, $userId,array $additionalData =[]): void
    {
        Log::get('响应', SysLogGroupConstant::HTTP)->info('响应', array_merge($additionalData, [
            'time' => $executionTime,
            'route' => $request->getUri()->getPath(),
            'status' => $response->getStatusCode(),
            'user_id' => $userId,
        ]));
    }

    protected function handleSlowRequest(ServerRequestInterface $request, float $executionTime, $userId): void
    {
        if ($executionTime > self::SLOW_REQUEST_THRESHOLD) {
            $this->sendSlowRequestAlert($request, $executionTime, $userId);
        }
    }

    protected function sendSlowRequestAlert(ServerRequestInterface $request, float $executionTime, $userId): void
    {
        Wechat::sendText([
            'title' => '执行时间过长',
            'app_name' => env('APP_NAME'),
            'run_time' => sprintf('%.4fs', $executionTime),
            'route' => $request->getUri()->getPath(),
            'clique' => $request->getHeaderLine('clique'),
            'version' => $request->getHeaderLine('version'),
            'comkey' => $request->getHeaderLine('comkey'),
            'repeat' => $request->getHeaderLine('repeat'),
            'appid' => $request->getHeaderLine('appid'),
            'query' => $request->getQueryParams(),
            'body' => $request->getParsedBody(),
            'user_id' => $userId,
            'trace' => Context::getOrSet(SysLogGroupConstant::REQUEST_ID, uniqid()),
        ]);
    }

    protected function calculateExecutionTime(float $startTime): float
    {
        return round(microtime(true) - $startTime, 2);
    }

    protected function getUserIdFromToken(ServerRequestInterface $request): string|int|null
    {
        try {
            $jwt = ApplicationContext::getContainer()->get(JwtFactory::class)->make();
            $payload = $jwt->getPayload();

            return $payload->get('sub');
        } catch (Exception $e) {
            // 可考虑添加日志记录：Log::debug('JWT token解析失败', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function getAdditionalRequestData(ServerRequestInterface $request): array
    {
        $data = [];
        foreach (config('hyperf_saas_helper.log.extra_headers_to_log', []) as $value) {
            $data[$value] = $request->getHeaderLine($value);
        }
        return $data;
    }
}