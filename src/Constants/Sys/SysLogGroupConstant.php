<?php

declare(strict_types=1);

namespace Faed\SaasHyperfHelper\Constants\Sys;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;

#[Constants]
class SysLogGroupConstant extends AbstractConstants
{

    #[Message('系统日志')]
    const SYS = 'sys';

    #[Message('请求日志')]
    const HTTP = 'http';

    #[Message('远程服务日志')]
    const FAR = 'far';

    #[Message('sql日志')]
    const SQL = 'sql';

    #[Message('系统预警日志')]
    const ALERT = 'alert';

    #[Message('日志附件属性')]
    const REQUEST_ID = 'log.request.id';
}
