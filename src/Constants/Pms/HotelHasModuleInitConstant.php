<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Constants\Pms;
use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Faed\HyperfSaasHelper\Traits\ConstantTrait;

#[Constants]
class HotelHasModuleInitConstant extends AbstractConstants
{
    use ConstantTrait;

    #[Message("未初始化")]
    const UNINITIALIZED = 1;

    #[Message("已初始化")]
    const INITIALIZED = 2;

    #[Message("无需初始化")]
    const NO_INITIALIZATION = 3;
}
