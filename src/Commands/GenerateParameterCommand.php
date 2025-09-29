<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Commands;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use function Hyperf\Config\config;
use function Hyperf\Support\now;

#[Command]
class GenerateParameterCommand extends HyperfCommand
{
    protected string $defaultConnection = 'default';

    protected array $excludeFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected array $typeMapping = [
        'int' => ['type' => 'integer', 'example' => 'randomInt'],
        'bigint' => ['type' => 'integer', 'example' => 'randomInt'],
        'tinyint' => ['type' => 'integer', 'example' => 'randomInt'],
        'smallint' => ['type' => 'integer', 'example' => 'randomInt'],
        'decimal' => ['type' => 'number', 'example' => 'randomFloat'],
        'float' => ['type' => 'number', 'example' => 'randomFloat'],
        'double' => ['type' => 'number', 'example' => 'randomFloat'],
        'json' => ['type' => 'array', 'example' => null],
        'datetime' => ['type' => 'string', 'example' => 'datetime'],
        'date' => ['type' => 'string', 'example' => 'date'],
        'timestamp' => ['type' => 'string', 'example' => 'datetime'],
        'time' => ['type' => 'string', 'example' => 'time'],
        'year' => ['type' => 'integer', 'example' => 'randomYear'],
    ];

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('faed:parameter');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('生成Request参数和swagger注释')
            ->addArgument('name', InputArgument::REQUIRED, '表名称')
            ->addArgument('connection', InputArgument::OPTIONAL, '数据库连接名称');
    }

    public function handle(): void
    {
        $tableName = $this->input->getArgument('name');
        $connection = $this->input->getArgument('connection') ?? $this->defaultConnection;

        try {
            $fieldComments = $this->getFieldComments($tableName, $connection);

            if (empty($fieldComments)) {
                $this->output->writeln('<error>未找到表或表没有字段: ' . $tableName . '</error>', OutputInterface::VERBOSITY_NORMAL);
                return;
            }

            $this->generateSwaggerParameters($fieldComments);
            $this->output->writeln(''); // 空行分隔
            $this->generateValidationRules($fieldComments);
        } catch (\Throwable $e) {
            $this->output->writeln('<error>生成参数时出错: ' . $e->getMessage() . '</error>', OutputInterface::VERBOSITY_NORMAL);
        }
    }

    protected function generateSwaggerParameters(array $fieldComments): void
    {
        $this->output->writeln('<comment>Swagger 参数注释:</comment>', OutputInterface::VERBOSITY_NORMAL);

        foreach ($fieldComments as $comment) {
            if ($this->shouldExcludeField($comment['name'])) {
                continue;
            }

            $required = $comment['is_nullable'] === 'NO' ? 'true' : 'false';
            [$type, $example] = $this->getFieldType($comment['type'], $comment['name'], $comment['comment']);

            $parameterTemplate = "#[OA\Parameter(name: '%s', description: '%s', in: 'query', required: %s, schema: new OA\Schema(type: '%s'%s), example: '%s')]";

            $additionalSchema = '';
            if ($type === 'array') {
                $additionalSchema = ", items: new OA\Items(type: 'string')";
            }

            $this->output->writeln(sprintf(
                $parameterTemplate,
                $comment['name'],
                addslashes($comment['comment']),
                $required,
                $type,
                $additionalSchema,
                addslashes((string)$example)
            ));
        }
    }

    protected function generateValidationRules(array $fieldComments): void
    {
        $this->output->writeln('<comment>验证规则:</comment>', OutputInterface::VERBOSITY_NORMAL);

        $rules = [];
        foreach ($fieldComments as $comment) {
            if ($this->shouldExcludeField($comment['name'])) {
                continue;
            }

            $rule = $comment['is_nullable'] === 'NO' ? 'required' : 'nullable';

            // 根据字段类型添加特定规则
            if (str_contains($comment['type'], 'int')) {
                $rule .= '|integer';
            } elseif (str_contains($comment['type'], 'decimal') || str_contains($comment['type'], 'float')) {
                $rule .= '|numeric';
            } elseif ($comment['type'] === 'json') {
                $rule .= '|array';
            } elseif ($comment['type'] === 'datetime' || $comment['type'] === 'date') {
                $rule .= '|date';
            } else {
                $rule .= '|string';
            }

            $rules[$comment['name']] = $rule;
        }

        // 格式化输出
        $maxLength = max(array_map('strlen', array_keys($rules)));
        foreach ($rules as $field => $rule) {
            $this->output->writeln(sprintf(
                "'%-{$maxLength}s' => '%s',",
                $field,
                $rule
            ));
        }
    }

    protected function getFieldType(string $dbType, string $fieldName, string $comment): array
    {
        foreach ($this->typeMapping as $typePattern => $mapping) {
            if (str_starts_with($dbType, $typePattern)) {
                $example = $mapping['example'];

                if ($example === 'randomInt') {
                    $example = random_int(1, 1000000);
                } elseif ($example === 'randomFloat') {
                    $example = random_int(1, 1000000) / 100;
                } elseif ($example === 'datetime') {
                    $example = now()->format('Y-m-d H:i:s');
                } elseif ($example === 'date') {
                    $example = now()->format('Y-m-d');
                } elseif ($example === 'time') {
                    $example = now()->format('H:i:s');
                } elseif ($example === 'randomYear') {
                    $example = random_int(2000, 2030);
                }

                // 特殊字段处理
                if ($fieldName === 'clique_id') {
                    $example = 60000004;
                } elseif ($fieldName === 'comkey') {
                    $example = 10000005;
                }

                return [$mapping['type'], $example ?? $comment];
            }
        }

        return ['string', $comment];
    }


    protected function getFieldComments(string $tableName, string $connection): array
    {
        $sql = "SELECT COLUMN_NAME, COLUMN_COMMENT, IS_NULLABLE, DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?";

        $results = Db::connection($connection)->select($sql, [$tableName]);

        return array_map(function ($row) {
            return [
                'name' => $row->COLUMN_NAME,
                'comment' => $row->COLUMN_COMMENT ?: $row->COLUMN_NAME,
                'is_nullable' => $row->IS_NULLABLE,
                'type' => strtolower($row->DATA_TYPE),
            ];
        }, $results);
    }

    protected function shouldExcludeField(string $fieldName): bool
    {
        return in_array($fieldName, $this->excludeFields, true);
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, '表名称'],
            ['connection', InputArgument::OPTIONAL, '数据库连接名称'],
        ];
    }
}