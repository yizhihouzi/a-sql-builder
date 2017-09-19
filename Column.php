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
    private $alias;

    /**
     * Column constructor.
     *
     * @param string $col
     * @param string $table
     * @param string $alias
     */
    public function __construct(string $col, Table $table = null, string $alias = null)
    {
        $this->table = $table;
        $this->col   = $col;
        $this->alias = $alias;
    }

    public function toSelectColStr()
    {
        $col = null;
        if (($this->table) instanceof Table) {
            $tableName = (string)$this->table;
            $col       = "$tableName.`$this->col`";
        } else {
            $col = $this->col;
        }
        if (is_string($this->alias)) {
            $col = "$col `$this->alias`";
        }
        return $col;
    }

    public function __toString()
    {
        $col = null;
        if (($this->table) instanceof Table) {
            $tableName = (string)$this->table;
            $col       = "$tableName.`$this->col`";
        } else {
            $col = $this->col;
        }
        return $col;
    }
}