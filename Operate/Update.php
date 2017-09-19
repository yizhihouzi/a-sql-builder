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

class Update extends DBOperate
{
    private $columnUpdateInfo = [];

    public function setColumn(Column $col, $value, $isScalarValue = true)
    {
        $this->columnUpdateInfo[] = [$col, $value, $isScalarValue];
        return $this;
    }

    private function createUpdateColStr()
    {
        $colUpdateStrArr = [];
        foreach ($this->columnUpdateInfo as $singleColumnUpdateInfo) {
            list($col, $value, $isScalarValue) = $singleColumnUpdateInfo;
            $colName = (string)$col;
            if ($isScalarValue) {
                $colUpdateStrArr[] = "$colName='$value'";
            } else {
                $colUpdateStrArr[] = "$colName=$value";
            }
        }
        return ' SET '.implode(',', $colUpdateStrArr);
    }

    public function prepare()
    {
        $table                = (string)$this->table;
        $updateColStr         = $this->createUpdateColStr();
        $lJoinStr             = $this->createLJoinStr();
        $rJoinStr             = $this->createRJoinStr();
        $whereStr             = $this->createWhereConditionStr();
        $groupByColStr        = $this->createGroupByColStr();
        $preStr               = "UPDATE $table $updateColStr $lJoinStr $rJoinStr $whereStr $groupByColStr";
        $lConditionValues     = $this->createLJoinConditionValueArr();
        $rConditionValues     = $this->createRJoinConditionValueArr();
        $whereConditionValues = $this->createWhereJoinConditionValueArr();
        return [$preStr, array_merge($lConditionValues, $rConditionValues, $whereConditionValues)];
    }
}