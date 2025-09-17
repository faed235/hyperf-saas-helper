<?php

namespace Faed\HyperfSaasHelper;

use Hyperf\Conditionable\HigherOrderWhenProxy;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Model\Model as BaseModel;

abstract class Model extends BaseModel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if ($this->connection == 'pms_meal'){
            $comkey = Context::get('comkey');
            $this->setConnection('meal_'.$comkey);
        }
        if ($this->connection == 'pms_hotel'){
            $comkey = Context::get('comkey');
            $this->setConnection('hotel_'.$comkey);
        }
    }
}