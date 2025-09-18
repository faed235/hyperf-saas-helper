<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Constants\Hotel;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Faed\HyperfSaasHelper\Traits\ConstantTrait;
#[Constants]
class GuestTypeConstant extends AbstractConstants
{
    use ConstantTrait;

    #[Message("散客")]
    const FIT = 1;

    #[Message("会员")]
    const MEMBER = 2;

    #[Message("协议")]
    const AGREEMENT = 3;

    #[Message("其他")]
    const OTHER = 4;

}
