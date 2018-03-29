<?php namespace App\Util\Enum;

/**
 * 账号冻结状态
 * @package \App\Manage\Library\Enum
 * @author gaojian291
 * @date 2016-11-17
 */

class UserFrozenStatus extends Enum
{
    const USER_NORMAL       = 0;                    //正常
    const USER_FROZEN       = 1;                    //冻结
    const USER_FOR_OPEN     = 2;                    //待开通
}
