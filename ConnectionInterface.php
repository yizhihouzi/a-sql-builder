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

    static function select(string $preStr, array $prepareValueArr);

    static function update(string $preStr, array $prepareValueArr);

    static function delete(string $preStr, array $prepareValueArr);

    static function insert(string $preStr, array $prepareValueArr);
}
