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

    private float $base;
    private bool $frozen = false; // 防止计算完成后被意外修改

    /**
     * 私有构造方法，防止直接实例化
     */
    private function __construct(float $base)
    {
        $this->base = $base;
    }

    /**
     * 初始化计算器
     */
    public static function init(float $base): self
    {
        return new self($base);
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
     * 加法
     */
    public function add(float $number): self
    {
        $this->ensureMutable();
        $this->base += $number;
        return $this;
    }

    /**
     * 减法
     */
    public function sub(float $number): self
    {
        $this->ensureMutable();
        $this->base -= $number;
        return $this;
    }

    /**
     * 乘法
     */
    public function multiplication(float $number): self
    {
        $this->ensureMutable();
        $this->base *= $number;
        return $this;
    }

    /**
     * 除法
     *
     * @throws InvalidArgumentException 当除数为0时
     */
    public function division(float $number): self
    {
        $this->ensureMutable();

        if ($number === 0.0) {
            throw new InvalidArgumentException('除数不能为零');
        }

        $this->base /= $number;
        return $this;
    }

    /**
     * 处理负数 - 如果当前值为负数则设置为0
     */
    public function negativeNumber(): self
    {
        $this->ensureMutable();

        if ($this->base < 0) {
            $this->base = 0;
        }
        return $this;
    }

    /**
     * 绝对值
     */
    public function abs(): self
    {
        $this->ensureMutable();
        $this->base = abs($this->base);
        return $this;
    }

    /**
     * 幂运算
     */
    public function power(float $exponent): self
    {
        $this->ensureMutable();
        $this->base **= $exponent;
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

        if ($this->base < 0) {
            throw new InvalidArgumentException('不能对负数开平方根');
        }

        $this->base = sqrt($this->base);
        return $this;
    }

    /**
     * 百分比计算 (返回基数乘以百分比后的值)
     *
     * @example Calculation::init(100)->percentage(50)->getResult() => 50
     */
    public function percentage(float $percent): self
    {
        $this->ensureMutable();
        $this->base *= $percent / 100;
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

        if ($this->base === 0.0) {
            throw new InvalidArgumentException('零没有倒数');
        }

        $this->base = 1 / $this->base;
        return $this;
    }

    /**
     * 获取原始值（不四舍五入）
     */
    public function getRawValue(): float
    {
        return $this->base;
    }

    /**
     * 获取结果
     *
     * @param int $precision 保留小数位数，默认为2
     * @param bool $roundUp 是否向上取整（默认四舍五入）
     */
    public function getResult(int $precision = 2, bool $roundUp = false): float
    {
        if ($precision < 0) {
            throw new InvalidArgumentException('精度不能为负数');
        }

        $multiplier = 10 ** $precision;

        if ($roundUp) {
            return ceil($this->base * $multiplier) / $multiplier;
        }

        return round($this->base, $precision);
    }

    /**
     * 格式化为货币字符串
     */
    public function toCurrency(string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        return number_format(
            $this->getResult(2),
            2,
            $decimalSeparator,
            $thousandsSeparator
        );
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
        if ($name === 'init') {
            if (!isset($arguments[0]) || !is_numeric($arguments[0])) {
                throw new InvalidArgumentException('初始化需要提供一个数字参数');
            }
            return self::init((float)$arguments[0]);
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
        return $this->{$name}((float)$arguments[0]);
    }

    /**
     * 克隆方法 - 创建计算器的副本
     */
    public function __clone()
    {
        $this->frozen = false; // 克隆后解除冻结状态
    }
}