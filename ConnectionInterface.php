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
    public static function getSchemaName();

    public static function select(string $preStr, array $prepareValueArr);
}
