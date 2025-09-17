<?php

namespace Faed\HyperfSaasHelper;

use InvalidArgumentException;
use RuntimeException;
use JsonSerializable;

/**
 * 流畅计算器类 - 支持链式调用的数学运算工具
 */
class Calculation implements JsonSerializable
{
    // 定义允许的操作方法（用于魔术方法验证）
    private const ALLOWED_METHODS = [
        'add', 'sub', 'multiplication', 'division', 'negativeNumber',
        'abs', 'power', 'sqrt', 'percentage', 'inverse'
    ];

    private string $base; // 使用字符串存储以保持精度
    private bool $frozen = false; // 防止计算完成后被意外修改
    private static bool $useBcMath = true; // 是否使用BC Math扩展

    /**
     * 私有构造方法，防止直接实例化
     */
    private function __construct(string|float|int $base)
    {
        $this->base = is_string($base) ? $base : (string)$base;

        // 检查BC Math是否可用
        if (self::$useBcMath && !function_exists('bcadd')) {
            self::$useBcMath = false;
        }
    }

    /**
     * 初始化计算器
     */
    public static function init(string|float|int $base): self
    {
        return new self($base);
    }

    /**
     * 设置是否使用BC Math扩展
     */
    public static function useBcMath(bool $use = true): void
    {
        self::$useBcMath = $use && function_exists('bcadd');
    }

    /**
     * 冻结计算器，防止后续修改
     */
    public function freeze(): self
    {
        $this->frozen = true;
        return $this;
    }

    /**
     * 检查是否可修改
     */
    private function ensureMutable(): void
    {
        if ($this->frozen) {
            throw new RuntimeException('计算结果已冻结，无法修改');
        }
    }

    /**
     * 执行高精度加法
     */
    private function bcAdd(string $left, string $right): string
    {
        return self::$useBcMath ? bcadd($left, $right, 10) : (string)($left + $right);
    }

    /**
     * 执行高精度减法
     */
    private function bcSub(string $left, string $right): string
    {
        return self::$useBcMath ? bcsub($left, $right, 10) : (string)($left - $right);
    }

    /**
     * 执行高精度乘法
     */
    private function bcMul(string $left, string $right): string
    {
        return self::$useBcMath ? bcmul($left, $right, 10) : (string)($left * $right);
    }

    /**
     * 执行高精度除法
     */
    private function bcDiv(string $left, string $right): string
    {
        if ($right === '0') {
            throw new InvalidArgumentException('除数不能为零');
        }
        return self::$useBcMath ? bcdiv($left, $right, 10) : (string)($left / $right);
    }

    /**
     * 加法
     */
    public function add(string|float|int $number): self
    {
        $this->ensureMutable();
        $number = is_string($number) ? $number : (string)$number;
        $this->base = $this->bcAdd($this->base, $number);
        return $this;
    }

    /**
     * 减法
     */
    public function sub(string|float|int $number): self
    {
        $this->ensureMutable();
        $number = is_string($number) ? $number : (string)$number;
        $this->base = $this->bcSub($this->base, $number);
        return $this;
    }

    /**
     * 乘法
     */
    public function multiplication(string|float|int $number): self
    {
        $this->ensureMutable();
        $number = is_string($number) ? $number : (string)$number;
        $this->base = $this->bcMul($this->base, $number);
        return $this;
    }

    /**
     * 除法
     *
     * @throws InvalidArgumentException 当除数为0时
     */
    public function division(string|float|int $number): self
    {
        $this->ensureMutable();
        $number = is_string($number) ? $number : (string)$number;
        $this->base = $this->bcDiv($this->base, $number);
        return $this;
    }

    /**
     * 处理负数 - 如果当前值为负数则设置为0
     */
    public function negativeNumber(): self
    {
        $this->ensureMutable();

        if (bccomp($this->base, '0', 10) < 0) {
            $this->base = '0';
        }
        return $this;
    }

    /**
     * 绝对值
     */
    public function abs(): self
    {
        $this->ensureMutable();
        $this->base = ltrim($this->base, '-');
        return $this;
    }

    /**
     * 幂运算
     */
    public function power(string|float|int $exponent): self
    {
        $this->ensureMutable();
        $exponent = is_string($exponent) ? $exponent : (string)$exponent;

        if (self::$useBcMath) {
            $result = '1';
            $isNegative = str_starts_with($exponent, '-');
            $exponent = ltrim($exponent, '-');

            // 简单实现，实际应用中可能需要更高效的算法
            for ($i = 0; $i < $exponent; $i++) {
                $result = bcmul($result, $this->base, 10);
            }

            if ($isNegative) {
                $result = bcdiv('1', $result, 10);
            }

            $this->base = $result;
        } else {
            $this->base = (string)pow($this->base, $exponent);
        }

        return $this;
    }

    /**
     * 平方根
     *
     * @throws InvalidArgumentException 当基数为负数时
     */
    public function sqrt(): self
    {
        $this->ensureMutable();

        if (bccomp($this->base, '0', 10) < 0) {
            throw new InvalidArgumentException('不能对负数开平方根');
        }

        if (self::$useBcMath) {
            // BC Math没有直接的sqrt函数，使用牛顿迭代法近似计算
            $x = $this->base;
            $guess = bcdiv($x, '2', 10);

            for ($i = 0; $i < 100; $i++) {
                $newGuess = bcdiv(
                    bcadd($guess, bcdiv($x, $guess, 10), 10),
                    '2',
                    10
                );

                if (bccomp($newGuess, $guess, 10) === 0) {
                    break;
                }

                $guess = $newGuess;
            }

            $this->base = $guess;
        } else {
            $this->base = (string)sqrt($this->base);
        }

        return $this;
    }

    /**
     * 百分比计算 (返回基数乘以百分比后的值)
     *
     * @example Calculation::init(100)->percentage(50)->getResult() => 50
     */
    public function percentage(string|float|int $percent): self
    {
        $this->ensureMutable();
        $percent = is_string($percent) ? $percent : (string)$percent;
        $this->base = $this->bcMul($this->base, bcdiv($percent, '100', 10));
        return $this;
    }

    /**
     * 取倒数
     *
     * @throws InvalidArgumentException 当基数为零时
     */
    public function inverse(): self
    {
        $this->ensureMutable();

        if ($this->base === '0') {
            throw new InvalidArgumentException('零没有倒数');
        }

        $this->base = bcdiv('1', $this->base, 10);
        return $this;
    }

    /**
     * 数组求和（保持精度）
     *
     * @param array $numbers 数字数组，可以是字符串、浮点数或整数
     * @return self
     */
    public static function sum(array $numbers): self
    {
        $sum = '0';
        foreach ($numbers as $number) {
            $number = is_string($number) ? $number : (string)$number;
            $sum = (new self($sum))->add($number)->getRawValue();
        }
        return new self($sum);
    }

    /**
     * 获取原始值（不四舍五入）
     */
    public function getRawValue(): string
    {
        return $this->base;
    }

    /**
     * 获取结果
     *
     * @param int $precision 保留小数位数，默认为2
     * @param bool $roundUp 是否向上取整（默认四舍五入）
     */
    public function getResult(int $precision = 2, bool $roundUp = false): string
    {
        if ($precision < 0) {
            throw new InvalidArgumentException('精度不能为负数');
        }

        $value = $this->base;

        // 处理科学计数法
        if (str_contains($value, 'E') || str_contains($value, 'e')) {
            $value = sprintf('%.'.$precision.'f', $value);
        }

        // 如果没有小数部分，直接返回
        if (!str_contains($value, '.')) {
            return $value;
        }

        // 处理精度
        $parts = explode('.', $value, 2);
        $integerPart = $parts[0];
        $decimalPart = isset($parts[1]) ? $parts[1] : '';

        // 截断或四舍五入
        if (strlen($decimalPart) > $precision) {
            if ($roundUp) {
                // 向上取整
                $rounded = bcadd($value, (string)('0.' . str_repeat('0', $precision) . '1'), $precision);
            } else {
                // 四舍五入
                $rounded = round($value, $precision, PHP_ROUND_HALF_UP);
                // 防止round函数使用科学计数法
                $rounded = sprintf('%.'.$precision.'f', $rounded);
            }

            // 去除可能的小数点后多余的零
            $rounded = rtrim($rounded, '0');
            $rounded = rtrim($rounded, '.') ?: '0';

            return $rounded;
        }

        // 补零
        return $value;
    }

    /**
     * 格式化为货币字符串
     */
    public function toCurrency(string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        $value = $this->getResult(2);

        // 处理负号
        $sign = '';
        if (strpos($value, '-') === 0) {
            $sign = '-';
            $value = substr($value, 1);
        }

        // 分割整数和小数部分
        $parts = explode('.', $value, 2);
        $integerPart = $parts[0];
        $decimalPart = isset($parts[1]) ? $parts[1] : '';

        // 添加千位分隔符
        $integerPart = number_format((int)$integerPart, 0, '', $thousandsSeparator);

        // 组合结果
        $result = $sign . $integerPart;
        if ($decimalPart !== '') {
            $result .= $decimalSeparator . $decimalPart;
        }

        return $result;
    }

    /**
     * 实现 JsonSerializable 接口
     */
    public function jsonSerialize(): array
    {
        return [
            'raw_value' => $this->getRawValue(),
            'rounded_value' => $this->getResult(),
            'formatted_value' => $this->toCurrency()
        ];
    }

    /**
     * 静态魔术方法调用
     */
    public static function __callStatic(string $name, array $arguments): self
    {
        if ($name === 'init' || $name === 'sum') {
            if (!isset($arguments[0])) {
                throw new InvalidArgumentException('初始化需要提供一个数字参数');
            }
            return self::$name($arguments[0]);
        }

        throw new RuntimeException("静态方法 [{$name}] 不存在");
    }

    /**
     * 动态魔术方法调用（已废弃，保留兼容性）
     *
     * @deprecated 建议直接使用定义好的方法
     */
    public function __call(string $name, array $arguments): self
    {
        if (!in_array($name, self::ALLOWED_METHODS)) {
            throw new RuntimeException("方法 [{$name}] 不存在或不允许调用");
        }

        if (empty($arguments)) {
            throw new InvalidArgumentException("方法 [{$name}] 需要至少一个参数");
        }

        // 保持向后兼容性
        return $this->{$name}($arguments[0]);
    }

    /**
     * 克隆方法 - 创建计算器的副本
     */
    public function __clone()
    {
        $this->frozen = false; // 克隆后解除冻结状态
    }
}