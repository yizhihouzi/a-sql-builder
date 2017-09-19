<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:19
 */

namespace DBOperate;

/**
 * Class Column
 *  应该允许存在不属于任何表的列
 * @package DBOperate
 */
class Column
{
    private $table;
    private $col;

    /**
     * Column constructor.
     * @param $table
     * @param $col
     */
    public function __construct(string $col, string $table = null)
    {
        $this->table = $table;
        $this->col = $col;
    }

    function __toString()
    {
        if (is_string($this->table)) {
            return "`$this->table`.`$this->col`";
        } else {
            return $this->col;
        }
    }
}