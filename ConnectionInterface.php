<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-6-8
 * Time: 下午10:09
 */

namespace DBOperate;

/**
 * Class Connection
 */
interface ConnectionInterface
{
    static function getSchemaName();

    static function select(Operate $operate);

    static function update(Operate $operate);

    static function insert(Operate $operate);
}
