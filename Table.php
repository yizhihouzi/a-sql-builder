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
    /**
     * eg:['table1'=>['col1','col2'],'table2'=>['col1','col2']]
     * @var array
     */
    private static $tableCols = [];
    private $table;

    /**
     * Table constructor.
     * @param $table
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    public function columnObjArr(array $cols): array
    {
        $cols = array_intersect($cols, $this->cols);
        $columnObjArr = [];
        foreach ($cols as $col) {
            $columnObjArr[] = new Column($col, $this->table);
        }
        return $columnObjArr;
    }

    public function cols()
    {
        return $this->cols;
    }

    function __get($name)
    {
        if ($name == 'cols') {
            if (!isset(self::$tableCols[$this->table])) {
                //查询表中列名
            }
            return self::$tableCols[$this->table];
        }
    }
}