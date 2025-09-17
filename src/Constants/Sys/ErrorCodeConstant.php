<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Faed\SaasHyperfHelper\Constants\Sys;

use Faed\HyperfSaasHelper\Traits\ConstantTrait;
use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;

#[Constants]
class ErrorCodeConstant extends AbstractConstants
{
    use ConstantTrait;
    #[Message("服务错误")]
    public const SERVER_ERROR = 100500;

    #[Message("签名错误")]
    public const SIGN_ERROR = 100409;

    #[Message("参数错误")]
    public const VERIFICATION_ERROR = 100422;

    #[Message("参数异常")]
    public const PARAMETER_ERROR = 100400;

    #[Message("未找到异常")]
    public const NOT_FOUND_ERROR = 100404;



}




