<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Commands;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use function Hyperf\Config\config;
use function Hyperf\Support\now;

#[Command]
class GenerateParameterCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('faed:parameter');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('生成Request参数和swagger注释');
    }

    public function handle(): void
    {
        $tableName  = $this->input->getArgument('name');
        $connection    = $this->input->getArgument('connection');
        $fieldComments = [];
        if ($connection){
            $fieldComments = $this->getFieldComments($tableName,$connection);
        }else{
            $connections = array_keys(config('databases'));
            foreach ($connections as $connection){
                $fieldComments = $this->getFieldComments($tableName,$connection);
                if ($fieldComments){
                    break;
                }
            }
        }

        foreach ($fieldComments as $comment){
            if (!in_array($comment['name'],['id','created_at','updated_at'])){
                $str = "#[OA\Parameter(name: '%s', description: '%s', in: 'query', required: %s, schema: new OA\Schema(type: '%s'), example:'%s')]";
                $required = $comment['is_nullable'] == 'NO'?'true':'false';
                list($type,$example) = $this->getFieldType($comment['type'],$comment['name'],$comment['comment']);
                $this->info(sprintf($str,$comment['name'],$comment['comment'],$required,$type,$example));
            }
        }
        foreach ($fieldComments as $comment){
            if (!in_array($comment['name'],['id','created_at','updated_at'])){
                $str = "'%s'=>['%s'],";
                $required = $comment['is_nullable'] == 'NO'?'required':'nullable';
                $this->info(sprintf($str,$comment['name'],$required));
            }
        }
    }



    public function getFieldType($type,$name,$comment): array
    {
        if (strpos($type, "int")){
            if ($name == 'clique_id'){
                $example = 60000004;
            }elseif ($name == 'comkey'){
                $example = 10000005;
            }else{
                $example = rand(100000,999999);
            }
            return ['integer',$example];
        }
        if ($type == 'json'){
            return ['array',null];
        }
        if ($type == 'datetime'){
            return ['string',now()->format('Y-m-d H:i:s')];
        }
        if ($type == 'date'){
            return ['string',now()->format('Y-m-d')];
        }
        return ['string',$comment];
    }

    function getFieldComments($tableName,$connection ='pms_public'): array
    {
        $sql = "SELECT COLUMN_NAME, COLUMN_COMMENT,IS_NULLABLE,DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?";

        $results = Db::connection($connection)->select($sql, [$tableName]);

        $fieldComments = [];
        foreach ($results as $row) {
            $fieldComments[] = [
                'name'=>$row->COLUMN_NAME,
                'comment'=>$row->COLUMN_COMMENT,
                'is_nullable'=>$row->IS_NULLABLE,
                'type'=>$row->DATA_TYPE,
            ];
        }

        return $fieldComments;
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, '主体名称'],
            ['connection', InputArgument::OPTIONAL, '主体名称'],
        ];
    }
}
