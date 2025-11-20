<?php

namespace Faed\HyperfSaasHelper;

use Hyperf\Conditionable\HigherOrderWhenProxy;
use Hyperf\Database\Model\Builder;
/**
 * @method static \Hyperf\Database\Model\Builder|self search(mixed $value, string $field, string $operation = '=')
 * @method static \Hyperf\Database\Model\Builder|self searchIn($value, $field, $operation = '=')
 * @method static \Hyperf\Database\Model\Builder|self searchLike($value,$field)
 * @method static \Hyperf\Database\Model\Builder|self searchTime($startTime = null , $endTime = null, string $field = 'created_at')
 * @method static \Hyperf\Database\Model\Builder|self fieldDate($value,string $field = 'created_at')
 * @method static \Hyperf\Database\Model\Builder|self startTime($value,string $field = 'created_at')
 * @method static \Hyperf\Database\Model\Builder|self endTime($value,string $field = 'created_at')
 * @method static \Hyperf\Database\Model\Builder|self hasSearch($name, $value, $field, string $operation='=')
 * @method static \Hyperf\Database\Model\Builder|self searchArray(array $data = [], array $fields = [])
 * @method static \Hyperf\Database\Model\Builder|self searchArrayLike(array $data = [], array $fields = [])
 */
trait ModelHelper
{
    public function scopeSearch(Builder $builder, $value, $field, string $operation='='): HigherOrderWhenProxy|Builder
    {
        return $builder->when($value,function (Builder $builder) use ($value,$field,$operation){
            $builder->where($field,$operation,$value);
        });
    }

    public function scopeSearchIn(Builder $builder, $value, $field): HigherOrderWhenProxy|Builder
    {
        return $builder->when($value,function (Builder $builder) use ($value,$field){
            if (is_array($value)){
                $builder->whereIn($field,$value);
            }else{
                $builder->whereIn($field,explode(',',$value));
            }
        });
    }

    public function scopeSearchLike(Builder $builder,$value,$field): HigherOrderWhenProxy|Builder
    {
        return $builder->when($value,function (Builder $builder,$value) use ($field){
            $builder->where($field,'like',"%{$value}%");
        });
    }

    public function scopeSearchTime(Builder $builder,$startTime = null , $endTime = null, string $field = 'created_at')
    {
        return $builder->startTime($startTime,$field)->endTime($endTime,$field);
    }

    public function scopeStartTime(Builder $builder,$value, string $field = 'created_at'): HigherOrderWhenProxy|Builder
    {
        return $builder->when($value,function (Builder $builder,$value) use ($field){
            $builder->where($field,'>=',$value);
        });
    }



    public function scopeEndTime(Builder $builder,$value, string $field = 'created_at'): HigherOrderWhenProxy|Builder
    {
        return $builder->when($value,function (Builder $builder,$value) use ($field){
            $builder->where($field,'<=',$value);
        });
    }

    public function scopeFieldDate(Builder $builder, $value,string $field = 'created_at'): HigherOrderWhenProxy|Builder
    {
        return $builder->when($value,function (Builder $builder) use ($value,$field){
            $builder->whereDate($field,$value);
        });
    }

    public function scopeHasSearch(Builder $builder,$name, $value, $field, string $operation='='): Builder
    {
        return $builder->when($value,function (Builder $builder) use ($name,$value,$field,$operation){
            $builder->whereHas($name,function (Builder $builder) use ($value,$field,$operation){
                if ($operation == 'like'){
                    $builder->where($field,'like','%'.$value.'%');
                }else{
                    $builder->where($field,$operation,$value);
                }
            });
        });
    }
    public function scopeSearchArray(Builder $builder,array $data = [],array $fields = []): Builder
    {
        foreach ($fields as $field){
            if (array_key_exists($field,$data)){
                $builder->when($data[$field],function (Builder $builder,$value) use ($field){
                    $builder->where($field,$value);
                });
            }
        }
        return $builder;
    }
    public function scopeSearchArrayLike(Builder $builder,array $data = [],array $fields = []): Builder
    {
        foreach ($fields as $field){
            if (array_key_exists($field,$data)){
                $builder->when($data[$field],function (Builder $builder,$value) use ($field){
                    $builder->where($field,'like',"%{$value}%");
                });
            }
        }
        return $builder;
    }
}