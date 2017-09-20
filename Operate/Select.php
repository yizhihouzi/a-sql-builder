<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:29
 */

namespace DBOperate\Operate;


use DBOperate\Column;
use DBOperate\Condition;
use DBOperate\DBOperate;
use DBOperate\Table;

class Select extends DBOperate
{
    private $fetchColumns = [];

    public function fetchCols(Column ...$cols)
    {
        $this->fetchColumns = array_merge($this->fetchColumns, $cols);
        return $this;
    }

    public function createSelectColStr()
    {
        $colsStrArr = [];
        foreach ($this->fetchColumns as $column) {
            /** @var Column $column */
            $colsStrArr[] = $column->toSelectColStr();
        }
        return implode(',', $colsStrArr);
    }

    public function prepareStr()
    {
        $table         = (string)$this->table;
        $selectColStr  = $this->createSelectColStr();
        $lJoinStr      = $this->createLJoinStr();
        $rJoinStr      = $this->createRJoinStr();
        $whereStr      = $this->createWhereConditionStr();
        $groupByColStr = $this->createGroupByColStr();
        return "SELECT $selectColStr FROM $table $lJoinStr $rJoinStr $whereStr $groupByColStr";
    }

    public function prepareValues()
    {
        $lConditionValues     = $this->createLJoinConditionValueArr();
        $rConditionValues     = $this->createRJoinConditionValueArr();
        $whereConditionValues = $this->createWhereJoinConditionValueArr();
        return array_merge($lConditionValues, $rConditionValues, $whereConditionValues);
    }
}