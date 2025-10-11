<?php

use Carbon\Carbon;
use Faed\HyperfSaasHelper\FluentCalculator;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use InvalidArgumentException as InvalidArgumentExceptionAlias;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (!function_exists('getRequest')) {
    /**
     * 获取当前 HTTP 请求对象（单例模式）
     *
     * 该函数用于从依赖注入容器中获取全局 Request 对象，
     * 如果函数已存在则不会重复定义（避免重复声明错误）。
     *
     * @return RequestInterface 返回 PSR-7 标准的请求对象实例
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @example
     * $request = getRequestObject();
     * $userAgent = $request->getHeaderLine('User-Agent');
     */
    function getRequest(): RequestInterface
    {
        $container = ApplicationContext::getContainer();
        return $container->get(RequestInterface::class);
    }
}

if (!function_exists('getRequestPageSize')) {
    /**
     * 从 HTTP 请求中获取分页大小参数（默认值：10）
     * @return int 返回请求中的 pageSize 参数值，若不存在则返回默认值 10
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function getRequestPageSize(): int
    {
        $request = getRequest(); // 假设 getRequest() 已定义
        $value = $request->input('pageSize', 10);
        if (!is_numeric($value)) {
            throw new InvalidArgumentExceptionAlias('pageSize must be numeric');
        }
        return (int)$value;
    }
}


if (!function_exists('generateDateRange')){
    /**
     * 生成两个日期之间的所有日期数组（包含起始和结束日期）
     * @param string $start 起始日期，格式为 'Y-m-d'（例如：'2023-01-01'）
     * @param string $end   结束日期，格式为 'Y-m-d'（例如：'2023-01-31'）
     * @return array 返回包含所有日期的数组，格式为 ['Y-m-d', 'Y-m-d', ...]
     * @throws Exception 如果日期解析失败或起始日期大于结束日期
     */
    // 改进版：添加参数校验和异常处理
    function generateDateRange(string $start, string $end): array
    {
        // 验证日期格式
        if (!strtotime($start) || !strtotime($end)) {
            throw new InvalidArgumentException('Invalid date format, expected Y-m-d');
        }

        // 确保起始日期不大于结束日期
        if (strtotime($start) > strtotime($end)) {
            return [];
            // 或者抛出异常：throw new InvalidArgumentException('Start date must be before end date');
        }

        $date = [];
        $current = $start;

        while (strtotime($current) <= strtotime($end)) {
            $date[] = $current;
            $current = Carbon::parse($current)->addDay()->format('Y-m-d');
        }

        return $date;
    }
}
if (!function_exists('multiGroupSum')){

    /**
     *多字段分组求和
     * @param array $data 原始数据数组
     * @param array $groupFields 分组字段名数组
     * @param array|string $sumFields 求和字段名（可以是单个字段名或字段名数组）
     * @return array 分组求和后的结果数组（扁平结构，类似MySQL GROUP BY结果）
     */
    function multiGroupSum(array $data, array $groupFields, array|string $sumFields): array
    {
        $result = [];

        // 如果求和字段是字符串，转换为数组
        if (is_string($sumFields)) {
            $sumFields = [$sumFields];
        }

        foreach ($data as $item) {
            // 生成分组键（使用分隔符连接多个分组字段的值）
            $groupKeyParts = [];
            foreach ($groupFields as $field) {
                $groupKeyParts[] = $item[$field] ?? 'undefined';
            }
            $groupKey = implode('|', $groupKeyParts);

            // 初始化分组记录（如果不存在）
            if (!isset($result[$groupKey])) {
                $groupRecord = [];
                // 添加分组字段值
                foreach ($groupFields as $field) {
                    $groupRecord[$field] = $item[$field] ?? 'undefined';
                }
                // 初始化求和字段
                foreach ($sumFields as $sumField) {
                    $groupRecord[$sumField] = 0;
                }
                $groupRecord['count'] = 0;
                $result[$groupKey] = $groupRecord;
            }

            // 更新求和字段和计数
            foreach ($sumFields as $sumField) {
                $result[$groupKey][$sumField] = FluentCalculator::init($result[$groupKey][$sumField])->add(floatval($item[$sumField] ?? 0))->getResult();
            }
            $result[$groupKey]['count']++;
        }

        // 重新索引数组（去掉分组键）
        return array_values($result);
    }
}


if (!function_exists('getTree')) {
    /**
     * 将扁平数组转换为树形结构
     *
     * @param array $data 原始数据数组
     * @param int $pid 父级ID，默认为0（顶级节点）
     * @param string $pidField 父级ID字段名，默认为'pid'
     * @param string $idField 子级ID字段名，默认为'id'
     * @param string $childrenKey 子节点数组键名，默认为'children'
     * @return array
     */
    function getTree(
        array $data,
        int $pid = 0,
        string $pidField = 'pid',
        string $idField = 'id',
        string $childrenKey = 'children'
    ): array {
        $tree = [];
        $itemsById = [];

        // 预处理：建立ID到项的映射
        foreach ($data as $item) {
            if (!isset($item[$idField])) {
                continue;
            }
            $itemsById[$item[$idField]] = $item;
        }

        // 构建树
        foreach ($itemsById as $item) {
            $parentId = $item[$pidField] ?? null;

            // 跳过无效的父级引用（可选）
            if ($parentId !== null && !isset($itemsById[$parentId]) && $parentId !== $pid) {
                continue;
            }

            if ($parentId === $pid) {
                // 顶级节点直接添加到树中
                $tree[] = &$itemsById[$item[$idField]];
            } else {
                // 子节点添加到父节点的children数组中
                if (isset($itemsById[$parentId])) {
                    if (!isset($itemsById[$parentId][$childrenKey])) {
                        $itemsById[$parentId][$childrenKey] = [];
                    }
                    $itemsById[$parentId][$childrenKey][] = &$itemsById[$item[$idField]];
                }
            }
        }

        // 清理引用并返回
        unset($itemsById);
        return $tree;
    }
}