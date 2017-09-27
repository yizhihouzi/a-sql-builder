<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:29
 */

namespace DBOperate\Operate;

use DBOperate\ArrayHelper;
use DBOperate\Column;
use DBOperate\Operate;
use DBOperate\Table;

class Update extends Operate
{
    private   $columnUpdateInfo = [];
    protected $withTableArr     = [];

    public function setColumn(Column $col, $value)
    {
        $this->columnUpdateInfo[] = [$col, $value];
        return $this;
    }

    public function with(Table ...$tableArr)
    {
        $this->withTableArr = array_merge($this->withTableArr, $tableArr);
    }

    public function createTablesStr()
    {
        $tablesStr = implode(',', $this->withTableArr);
        $tablesStr = "$this->table,$tablesStr";
        return $tablesStr;
    }

    private function createUpdateColStr()
    {
        $colUpdateStrArr = [];
        foreach ($this->columnUpdateInfo as $singleColumnUpdateInfo) {
            list($col, $value) = $singleColumnUpdateInfo;
            $colName       = (string)$col;
            $isScalarValue = is_scalar($value);
            if ($isScalarValue) {
                $colUpdateStrArr[] = "$colName='$value'";
            } else {
                if ($value instanceof Select) {
                    $valueStr          = $value->prepareStr();
                    $colUpdateStrArr[] = "$colName=($valueStr)";
                } else if (is_null($value)) {
                    $colUpdateStrArr[] = "$colName=null";
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
            list(, $value) = $singleColumnUpdateInfo;
            $isScalarValue = is_scalar($value);
            if (!$isScalarValue) {
                if ($value instanceof Select) {
                    $colUpdateValueArr[] = $value->prepareValues();
                }
            }
        }
        return ArrayHelper::flatten($colUpdateValueArr);
    }

    public function prepareStr()
    {
        $tablesStr     = self::createTablesStr();
        $updateColStr  = $this->createUpdateColStr();
        $lJoinStr      = $this->createLJoinStr();
        $rJoinStr      = $this->createRJoinStr();
        $whereStr      = $this->createWhereConditionStr();
        $groupByColStr = $this->createGroupByColStr();
        return "UPDATE $tablesStr $updateColStr $lJoinStr $rJoinStr $whereStr $groupByColStr";
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