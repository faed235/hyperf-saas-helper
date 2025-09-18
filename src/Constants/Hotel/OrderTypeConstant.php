<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Constants\Hotel;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Faed\HyperfSaasHelper\Traits\ConstantTrait;

#[Constants]
class OrderTypeConstant extends AbstractConstants
{
    use ConstantTrait;

    #[Message("日租")]
    const DAY = 'day';

    #[Message("钟点房")]
    const HOUR = 'hour';
}
