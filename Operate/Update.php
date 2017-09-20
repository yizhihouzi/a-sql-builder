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
                if ($value instanceof Select) {
                    $valueStr          = $value->prepareStr();
                    $colUpdateStrArr[] = "$colName=($valueStr)";
                } else {
                    $colUpdateStrArr[] = "$colName=$value";
                }
            }
        }
        return 'SET ' . implode(',', $colUpdateStrArr);
    }

    private function createUpdateColValues()
    {
        $colUpdateValueArr = [];
        foreach ($this->columnUpdateInfo as $singleColumnUpdateInfo) {
            list(, $value, $isScalarValue) = $singleColumnUpdateInfo;
            if (!$isScalarValue) {
                if ($value instanceof Select) {
                    $colUpdateValueArr[] = $value->prepareValues();
                }
            }
        }
        return self::flatten($colUpdateValueArr);
    }

    public function prepareStr()
    {
        $table         = (string)$this->table;
        $updateColStr  = $this->createUpdateColStr();
        $lJoinStr      = $this->createLJoinStr();
        $rJoinStr      = $this->createRJoinStr();
        $whereStr      = $this->createWhereConditionStr();
        $groupByColStr = $this->createGroupByColStr();
        return "UPDATE $table $updateColStr $lJoinStr $rJoinStr $whereStr $groupByColStr";
    }

    public function prepareValues()
    {
        $updatePrepareValues  = $this->createUpdateColValues();
        $lConditionValues     = $this->createLJoinConditionValueArr();
        $rConditionValues     = $this->createRJoinConditionValueArr();
        $whereConditionValues = $this->createWhereJoinConditionValueArr();
        return array_merge($updatePrepareValues, $lConditionValues, $rConditionValues, $whereConditionValues);
    }
}