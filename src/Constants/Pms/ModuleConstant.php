<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Constants\Pms;
use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Faed\HyperfSaasHelper\Traits\ConstantTrait;

#[Constants]
class ModuleConstant extends AbstractConstants
{
    use ConstantTrait;

    #[Message("pms-hotel")]
    public const HOTEL = 1;
    #[Message("pms-meal")]
    public const MEAL = 2;

    #[Message("pms-electric")]
    public const ELECTRIC = 3;
}
