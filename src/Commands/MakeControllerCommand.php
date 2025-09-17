<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Commands;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class MakeControllerCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('faed:controller');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('同步创建controller和Request');
    }

    public function handle(): void
    {
        $name = $this->input->getArgument('name');
        $this->call("gen:controller",['name'=>"{$name}Controller"]);
        $this->call("gen:request",['name'=>"{$name}Request"]);
    }


    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, '主体名称']
        ];
    }
}
