<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:19
 */

namespace DBOperate;

use DBOperate\Table\InformationSchema;

/**
 * Class Table
 * @property array cols
 * @package DBOperate
 */
class Table
{
    /**
     * eg:['table1'=>['col1','col2'],'table2'=>['col1','col2']]
     * @var array
     */
    private static $tableCols = [];
    private        $tableName;
    private        $tableAliasName;

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

    public function cols()
    {
        return $this->cols;
    }

    public function __get($name)
    {
        if ($name == 'cols') {
            if (!isset(self::$tableCols[$this->tableName])) {
                $cols                              =
                    InformationSchema::getTableCols($this->tableName);
                self::$tableCols[$this->tableName] = &$cols;
            }
            return self::$tableCols[$this->tableName];
        } elseif (in_array($colName = self::unCamelize($name), $this->cols)) {
            return new Column($colName, $this);
        }
        return null;
    }

    /**
     * 驼峰命名转下划线命名
     * 思路:
     * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     *
     * @param        $camelCaps
     * @param string $separator
     *
     * @return string
     */
    private static function unCamelize($camelCaps, $separator = '_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    function __toString()
    {
        $aliasName = (!empty($this->tableAliasName)) ? " `$this->tableAliasName`" : '';
        return "`$this->tableName`$aliasName";
    }
}