<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:29
 */

namespace DBOperate\Operate;


use DBOperate\Column;
use DBOperate\DBOperate;

class Select extends DBOperate
{
    private $fetchColumns = [];
    private $limitStart, $limitEnd;

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

    public function limit(int $start, int $end)
    {
        $this->limitStart = $start;
        $this->limitEnd   = $end;
    }

    public function prepareStr()
    {
        $table         = (string)$this->table;
        $selectColStr  = $this->createSelectColStr();
        $lJoinStr      = $this->createLJoinStr();
        $rJoinStr      = $this->createRJoinStr();
        $whereStr      = $this->createWhereConditionStr();
        $groupByColStr = $this->createGroupByColStr();
        $preStr        = "SELECT $selectColStr FROM $table $lJoinStr $rJoinStr $whereStr $groupByColStr";
        if (is_int($this->limitStart) && is_int($this->limitEnd)) {
            $preStr = "$preStr limit $this->limitStart,$this->limitEnd";
        }
        return $preStr;
    }

    public function prepareValues()
    {
        $lConditionValues     = $this->createLJoinConditionValueArr();
        $rConditionValues     = $this->createRJoinConditionValueArr();
        $whereConditionValues = $this->createWhereJoinConditionValueArr();
        return array_merge($lConditionValues, $rConditionValues, $whereConditionValues);
    }
}