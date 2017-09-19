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

    public function prepare()
    {
        $table                = (string)$this->table;
        $selectColStr         = $this->createSelectColStr();
        $lJoinStr             = $this->createLJoinStr();
        $rJoinStr             = $this->createRJoinStr();
        $whereStr             = $this->createWhereConditionStr();
        $groupByColStr        = $this->createGroupByColStr();
        $preStr               = "SELECT $selectColStr FROM $table $lJoinStr $rJoinStr $whereStr $groupByColStr";
        $lConditionValues     = $this->createLJoinConditionValueArr();
        $rConditionValues     = $this->createRJoinConditionValueArr();
        $whereConditionValues = $this->createWhereJoinConditionValueArr();
        return [$preStr, array_merge($lConditionValues, $rConditionValues, $whereConditionValues)];
    }

    function __toString()
    {
        $prepareResult = $this->prepare();
        return json_encode($prepareResult);
    }
}