<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2018/8/13
 * Time: 10:06
 */

namespace DBOperate\Exception;

class DBOperateException extends \Exception
{
    /**
     * @throws DBOperateException
     */
    public static function nonUpdateColumn()
    {
        throw new DBOperateException('there must be some update column while update-operate.');
    }

    /**
     * @throws DBOperateException
     */
    public static function invalidConditionValue()
    {
        throw new DBOperateException('Condition:$value must be array type while $isScalarValue==true and $relation=="in"');
    }

}
