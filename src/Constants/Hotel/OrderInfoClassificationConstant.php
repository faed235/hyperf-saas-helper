<?php

declare(strict_types=1);

namespace Faed\HyperfSaasHelper\Constants\Hotel;

use Faed\HyperfSaasHelper\Traits\ConstantTrait;
use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
class OrderInfoClassificationConstant extends AbstractConstants
{
    use ConstantTrait;


    #[Message("日租房费")]
    const DAY_ROOM_ROOM_RATE              =   1;

    #[Message("钟点房费")]
    const HOUR_ROOM_ROOM_RATE             =   2;

    #[Message("延时(小时)房费")]
    const DELAYED_ROOM_RATE_HOURS         =   3;

    #[Message("延时(半日)房费")]
    const EXTENDED_ROOM_RATE_FOR_HALF_A_DAY  =   4;

    #[Message("延时(天)")]
    const EXTENDED_ROOM_RATE_FOR_DAY       =   5;

    #[Message("商品")]
    const COMMODITY                    =   6;

    #[Message("其他")]
    const OTHER                        =   7;

    #[Message("换房")]
    const CHANGE_ROOM                   =   8;

    #[Message("对冲数据")]
    const HEDGE                        =   9;

    #[Message("拆账")]
    const SPLIT                        =   10;

    #[Message("长租")]
    const LONG_RENT                     =   11;

    #[Message("长租项目")]
    const LONG_RENT_PROJECT              =   12;

    #[Message("点餐挂帐")]
    const HANG_HOTEL                    =   13;
}
