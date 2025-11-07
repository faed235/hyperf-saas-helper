<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Commands;

use DirectoryIterator;
use GuzzleHttp\Client;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ApplicationInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use function Hyperf\Config\config;

#[Command]
class UpApifoxCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('faed:apifox');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('发布到apifox');
    }

    public function handle(): void
    {
        $command = 'gen:swagger';
        $params = ["command" => $command];
        $input = new ArrayInput($params);
        $output = new NullOutput();

        $container = ApplicationContext::getContainer();
        $application = $container->get(ApplicationInterface::class);
        $application->setAutoExit(false);
        $exitCode = $application->run($input, $output);
        $this->info(sprintf('生成文档完成:%s',$exitCode));

        $directory = config('swagger.json_dir');
        $iterator = new DirectoryIterator($directory);
        $files = [];
        foreach ($iterator as $fileinfo) {
            // 检查是否是文件（不是目录或链接等）
            if ($fileinfo->isFile()) {
                $file = $fileinfo->getPathname();
                $this->info(sprintf('上传的文件:%s',$file));
                $files[] = $fileinfo->getPathname();
            }
        }

        $url = sprintf('https://api.apifox.com/v1/projects/%d/import-openapi',config('hyperf_saas_helper.apifox.apifox_project_id'));

        foreach ($files as $file){
            $json = file_get_contents($file);
            $apiArray = json_decode($json,true);
            $client = new Client();
            $response = $client->post($url,[
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'X-Apifox-Api-Version'=>config('hyperf_saas_helper.apifox.apifox_version'),
                    'Authorization'=>config('hyperf_saas_helper.apifox.apifox_token'),
                ],
                'json'=>[
                    'input'=>json_encode($apiArray),
                ],
            ]);
            $content = $response->getBody()->getContents();
            foreach (json_decode($content,true)['data']['counters'] as $key=>$value){
                $this->info($key.'=>'.$value);
            }
        }


    }
}
