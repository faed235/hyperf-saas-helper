<?php

namespace Faed\HyperfSaasHelper\Traits;

use ReflectionClass;

trait ConstantTrait
{
    public static function asKeyValue(): array
    {
        $result = [];
        foreach (self::getConstants() as $value){
            $result[] = [
                'key'=>$value,
                'value'=>self::getMessage($value),
            ];
        }
        return $result;
    }

    public static function asSelectArray(): array
    {
        $result = [];
        foreach (self::getConstants() as $value){
            $result[$value] = self::getMessage($value);
        }
        return $result;
    }
    public static function asArray(): array
    {
        return self::getConstants();
    }

    public static function getConstants(): array
    {
        $reflectionClass = new ReflectionClass(self::class);
        return $reflectionClass->getConstants();
    }
}