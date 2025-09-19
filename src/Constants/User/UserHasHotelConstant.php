<?php

namespace Faed\HyperfSaasHelper\Constants\User;
use Faed\HyperfSaasHelper\Traits\ConstantTrait;
use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;

#[Constants]
class UserHasHotelConstant
{
    use ConstantTrait;

    #[Message("集团后台")]
    const CLIQUE = 'clique';

    #[Message("pos端")]
    const POS = 'pos';

    #[Message("商户后台")]
    const MERCHANT = 'merchant';

    #[Message("只关联无权限")]
    const LINK = 'link';

    #[Message("周边商家")]
    const SUPPLIER = 'supplier';

    #[Message("周边商家")]
    const ELECTRIC_APPLET = 'ELECTRIC_APPLET';
}