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
    }

    public function handle(): void
    {
        $tableName = $this->input->getArgument('name');
        $specifiedConnection = $this->input->getArgument('connection');

        try {
            $fieldComments = $this->getFieldComments($tableName, $specifiedConnection);

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
            if ($this->isExcludedColumn($comment['name'])) {
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

        foreach ($fieldComments as $comment) {
            if ($this->isExcludedColumn($comment['name'])) {
                continue;
            }
            $fieldName = $comment['name'];
            $isNullable = $comment['is_nullable'] === 'NO';
            $baseType = preg_replace('/\(.*\)/', '', $comment['type']);
            $baseType = strtolower($baseType);
            $fieldComment = $comment['comment'] ?? '';

            // Start with required/nullable
            $rule = $isNullable ?  'required' :'nullable';

            // Add type-specific rules
            // Integer types
            if (in_array($baseType, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint'])) {
                // Boolean fields (tinyint(1))
                if ($baseType === 'tinyint' && strpos($comment['type'], '(1)') !== false) {
                    $rule .= '|boolean';
                } else {
                    $rule .= '|integer';
                    // Add size validation for known ID fields
                    if ($fieldName === 'clique_id') {
                        $rule .= '|min:60000000|max:99999999';
                    } elseif ($fieldName === 'comkey') {
                        $rule .= '|min:10000000|max:99999999';
                    }
                }
            }
            // Decimal/Float types
            elseif (in_array($baseType, ['decimal', 'float', 'double'])) {
                $rule .= '|numeric';
            }
            // Boolean (could be enum('Y','N') or other representations)
            elseif ($baseType === 'enum' && in_array(strtoupper($fieldComment), ['Y', 'N'])) {
                $rule .= '|boolean';
            }
            // JSON types
            elseif ($baseType === 'json') {
                $rule .= '|array';
            }
            // Date/Time types
            elseif (in_array($baseType, ['datetime', 'timestamp', 'date', 'time'])) {
                $rule .= '|date';
            }
            // Email fields
            elseif (strpos($fieldName, 'email') !== false || strpos($fieldComment, 'email') !== false) {
                $rule .= '|email';
            }
            // URL fields
            elseif (strpos($fieldName, 'url') !== false || strpos($fieldComment, 'url') !== false) {
                $rule .= '|url';
            }
            // Phone fields (simple validation)
            elseif (strpos($fieldName, 'phone') !== false || strpos($fieldComment, 'phone') !== false) {
                $rule .= '|regex:/^1[3-9]\d{9}$/';
            }
            // String types with length validation
            elseif (in_array($baseType, ['char', 'varchar', 'text'])) {
                // Extract length from type (e.g. varchar(255) -> 255)
                if (preg_match('/\((\d+)\)/', $comment['type'], $matches)) {
                    $maxLength = $matches[1];
                    $rule .= "|string|max:{$maxLength}";
                } else {
                    $rule .= '|string';
                }
            }
            // Default to string
            else {
                $rule .= '|string';
            }

            // Add special rules based on field name or comment
            if (strpos($fieldName, 'password') !== false) {
                $rule .= '|min:6';
            }
            if (strpos($fieldName, '_at') !== false && $baseType === 'datetime') {
                $rule .= '|date_format:Y-m-d H:i:s';
            }

            $rules[$fieldName] = $rule;
        }

        // Output the rules in a more readable format
        $this->info("\nValidation Rules:");
        $this->info('[');
        foreach ($rules as $field => $rule) {
            $result = str_replace('|', '\',\'', $rule);
            $this->info("    '{$field}' => ['{$result}'],");
        }
        $this->info(']');
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

    protected function getFieldComments(string $tableName, string $connection = null): array
    {
        $connections = $this->getMysqlConnections();

        if ($connection) {
            $connections = array_intersect($connections, [$connection]);
        }

        foreach ($connections as $conn) {
            try {
                $sql = "SELECT
                        COLUMN_NAME,
                        COLUMN_COMMENT,
                        IS_NULLABLE,
                        DATA_TYPE
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?";

                $results = DB::connection($conn)->select($sql, [$tableName]);

                if (!empty($results)) {
                    return array_map(function ($row) {
                        return [
                            'name' => $row->COLUMN_NAME,
                            'comment' => $row->COLUMN_COMMENT ?: $row->COLUMN_NAME,
                            'is_nullable' => $row->IS_NULLABLE,
                            'type' => $row->DATA_TYPE,
                        ];
                    }, $results);
                }
            } catch (\Exception $e) {
                $this->warn("Connection '{$conn}' failed: " . $e->getMessage());
                continue;
            }
        }

        return [];
    }

    /**
     * Get all MySQL connections from config
     *
     * @return array
     */
    protected function getMysqlConnections(): array
    {
        return array_keys(config('databases'));
    }

    protected function isExcludedColumn(string $fieldName): bool
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