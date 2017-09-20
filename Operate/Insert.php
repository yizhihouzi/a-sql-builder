<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-9-19
 * Time: 下午6:26
 */

namespace DBOperate\Operate;

use DBOperate\DBOperate;

class Insert extends DBOperate
{
    private $insertInfo = [];

    public function setInsertValues(array $cols, array ...$values)
    {
        $this->insertInfo = [$cols, $values];
        return $this;
    }

    private function createInsertColStr()
    {
        if (empty($this->insertInfo)) {
            return false;
        }
        list($cols, $values) = $this->insertInfo;
        $colStr = implode(',', $cols);
        $colStr = "($colStr)";
        foreach ($values as $key => $value) {
            $valueStr     = implode(',', $value);
            $values[$key] = "($valueStr)";
        }
        $valuesStr = implode(',', $values);
        return "$colStr VALUES $valuesStr";
    }

    public function prepareStr()
    {
        $table         = (string)$this->table;
        $insertColStr  = $this->createInsertColStr();
        $lJoinStr      = $this->createLJoinStr();
        $rJoinStr      = $this->createRJoinStr();
        $whereStr      = $this->createWhereConditionStr();
        $groupByColStr = $this->createGroupByColStr();
        return "INSERT INTO $table $insertColStr $lJoinStr $rJoinStr $whereStr $groupByColStr";
    }

    public function prepareValues()
    {
        $lConditionValues     = $this->createLJoinConditionValueArr();
        $rConditionValues     = $this->createRJoinConditionValueArr();
        $whereConditionValues = $this->createWhereJoinConditionValueArr();
        return array_merge($lConditionValues, $rConditionValues, $whereConditionValues);
    }
}
