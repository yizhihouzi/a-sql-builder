<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-6-8
 * Time: 下午10:09
 */

namespace DBOperate;

use DBOperate\Operate\Delete;
use DBOperate\Operate\Insert;
use DBOperate\Operate\Select;
use DBOperate\Operate\Update;

/**
 * Class Connection
 */
interface ConnectionInterface
{
    static function getSchemaName();

    static function select(Select $operate): array;

    static function update(Update $operate): int;

    static function insert(Insert $operate): int;

    static function delete(Delete $operate): int;
}
