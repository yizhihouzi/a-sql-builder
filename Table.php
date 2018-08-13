<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:19
 */

namespace DBOperate;

/**
 * Class Table
 * @property array cols
 * @package DBOperate
 */
class Table
{
    private $tableName;
    private $tableAliasName;

    /**
     * Table constructor.
     *
     * @param string $table
     */
    public function __construct(string $table)
    {
        $this->tableName = $table;
    }

    /**
     * @param array|null $cols
     * @param bool       $invert
     *
     * @return array|bool
     */
    public function columnObjArr(array $cols = null, $invert = false)
    {
        $cols       = is_array($cols) ? $cols : [];
        $colNameArr = [];
        if (!empty($cols)) {
            foreach ($cols as $key => $v) {
                if (is_numeric($key)) {
                    $colNameArr[] = $v;
                } else {
                    $colNameArr[] = $key;
                }
            }
        }
        if (!empty($colNameArr)) {
            if (!$invert) {
                $colNameArr = array_intersect($colNameArr, $this->cols);
            } else {
                $colNameArr = array_diff($this->cols, $colNameArr);
            }
        } else {
            $colNameArr = $this->cols;
        }
        if (!is_array($colNameArr)) {
            return false;
        }
        $columnObjArr = [];
        foreach ($colNameArr as $v) {
            if (!array_key_exists($v, $cols)) {
                $columnObjArr[] = new Column($v, $this);
            } else {
                $columnObjArr[] = new Column($v, $this, $cols[$v]);
            }
        }
        return $columnObjArr;
    }

    public function withName(string $name)
    {
        $new                 = clone $this;
        $new->tableAliasName = trim($name, '`');
        return $new;
    }

    public function name()
    {
        $name = $this->tableAliasName ?? $this->tableName;
        return "`$name`";
    }

    public function __get($colName)
    {
        return new Column($colName, $this);
    }

    function __toString()
    {
        $aliasName = (!empty($this->tableAliasName)) ? " `$this->tableAliasName`" : '';
        return "`$this->tableName`$aliasName";
    }
}