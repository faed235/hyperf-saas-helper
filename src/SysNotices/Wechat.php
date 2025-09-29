<?php

namespace Faed\HyperfSaasHelper\SysNotices;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use function Hyperf\Config\config;
use function Hyperf\Support\env;
use function Hyperf\Support\now;

class Wechat
{
    public static function sendText(string|array $text = [], string $level = 'debug'): array|string
    {
        $data = [];
        if (is_string($text)){
            $data['内容'] = $text;
        }else{
            $data = $text;
        }
        $data['time']=now()->format('Y-m-d H:i:s');

        $argument = [];
        foreach ($data as $k=>$v){
            if (is_array($v)){
                $argument[]=sprintf("%s : %s", $k, json_encode($v,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            }else{
                $argument[]=sprintf("%s : %s", $k, $v);
            }

        }
        $text = join(PHP_EOL, $argument);
        $url = self::getUri($level);
        $data = ['msgtype' => 'text', 'text' => ['content' => $text]];
        return self::request($url, $data);
    }

    private static function getUri(string $level): string
    {
        return config('hyperf_saas_helper.sys_notice.wechat')[$level] ?? '';
    }

    private static function request($url,$data): array
    {
        if (!config('hyperf_saas_helper.sys_notice.wechat.enable')){
            return [];
        }
        if (!$url){
            return [];
        }
        $client = new Client();
        try {
            $response = $client->post($url,[
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'json' => $data,
            ]);
        }catch (GuzzleException $exception){
            return [];
        }
        $content = $response->getBody()->getContents();
        return json_decode($content,true);
    }
}