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
use DBOperate\Condition;
use DBOperate\Operate;
use DBOperate\Table;

class Select extends Operate
{
    private $fetchColumns = [];
    private $limitStart, $limitEnd;

    private $groupByColumns  = [];
    private $orderByInfo     = [];
    private $whereConditions = [];
    private $lJoinInfo       = [];
    private $rJoinInfo       = [];

    public function fetchCols(...$cols)
    {
        $this->fetchColumns = array_merge($this->fetchColumns, ArrayHelper::flatten($cols));
        return $this;
    }

    public function createSelectColStr()
    {
        if (!empty($this->fetchColumns)) {
            $colsStrArr = [];
            foreach ($this->fetchColumns as $column) {
                /** @var Column $column */
                $colsStrArr[] = $column->toSelectColStr();
            }
            return implode(',', $colsStrArr);
        } else {
            return '*';
        }
    }

    public function where(Condition ...$conditions)
    {
        $this->whereConditions = array_merge($this->whereConditions, $conditions);
        return $this;
    }

    public function lJoin(Table $table, Condition ...$conditions)
    {
        $this->lJoinInfo[] = [$table, $conditions];
        return $this;
    }

    public function rJoin(Table $table, Condition ...$conditions)
    {
        $this->rJoinInfo[] = [$table, $conditions];
        return $this;
    }

    private static function createConditionArrStr(array $conditionArr)
    {
        if (empty($conditionArr)) {
            return '1';
        }
        $conditionGroup = [];
        foreach ($conditionArr as $condition) {
            if ($condition instanceof Condition) {
                $conditionGroup[$condition->getGroupName()][] = (string)$condition;
            } else {
                throw new \Exception("$condition can not transform to Condition type");
            }
        }
        foreach ($conditionGroup as $key => $item) {
            $conditionGroup[$key] = '(' . implode(' AND ', $item) . ')';
        }
        return implode(' OR ', $conditionGroup);
    }

    private static function createConditionValueArr(...$conditionArr)
    {
        $values       = [];
        $conditionArr = ArrayHelper::flatten($conditionArr);
        foreach ($conditionArr as $condition) {
            if ($condition instanceof Condition) {
                if (($v = $condition->getValue()) !== false) {
                    $values[] = $v;
                }
            } else {
                throw new \Exception("$condition can not transform to Condition type");
            }
        }
        return ArrayHelper::flatten($values);
    }

    private static function createJoinStr($joinInfo, $joinDirection)
    {
        $lJoinStrArr = [];
        foreach ($joinInfo as $lJoinItem) {
            $conditionArr  = $lJoinItem[1];
            $conditionStr  = self::createConditionArrStr($conditionArr);
            $tableName     = (string)$lJoinItem[0];
            $lJoinStrArr[] = " $joinDirection JOIN $tableName ON $conditionStr ";
        }
        return implode('', $lJoinStrArr);
    }

    private function createLJoinStr()
    {
        return self::createJoinStr($this->lJoinInfo, 'LEFT');
    }

    private function createLJoinConditionValueArr()
    {
        $conditionArr = array_column($this->lJoinInfo, 1);
        return self::createConditionValueArr($conditionArr);
    }

    private function createRJoinConditionValueArr()
    {
        $conditionArr = array_column($this->rJoinInfo, 1);
        return self::createConditionValueArr($conditionArr);
    }

    private function createRJoinStr()
    {
        return self::createJoinStr($this->rJoinInfo, 'RIGHT');
    }

    private function createWhereConditionStr()
    {
        if (!empty($this->whereConditions)) {
            return 'WHERE ' . self::createConditionArrStr($this->whereConditions);
        }
        return '';
    }

    private function createWhereJoinConditionValueArr()
    {
        return self::createConditionValueArr($this->whereConditions);
    }

    public function createGroupByColStr()
    {
        if (empty($this->groupByColumns)) {
            return '';
        }
        $colStrArr = [];
        foreach ($this->groupByColumns as $column) {
            $colStrArr[] = (string)$column;
        }
        $colStr = implode(',', $colStrArr);
        return "GROUP BY ($colStr) ";
    }

    public function groupBy(Column ...$cols)
    {
        $this->groupByColumns = array_merge($this->groupByColumns, $cols);
        return $this;
    }

    public function createOrderByStr()
    {
        if (empty($this->orderByInfo)) {
            return '';
        } else {
            return 'ORDER BY ' . implode(',', $this->orderByInfo);
        }
    }

    public function orderBy(Column $col, bool $asc = true)
    {
        $this->orderByInfo[] = "`{$col->colName()}`" . ($asc ? ' ASC' : ' DESC');
    }

    public function limit(int $start, int $end)
    {
        $this->limitStart = $start;
        $this->limitEnd   = $end;
    }

    public function prepareStr()
    {
        $tablesStr     = $this->table;
        $selectColStr  = $this->createSelectColStr();
        $lJoinStr      = $this->createLJoinStr();
        $rJoinStr      = $this->createRJoinStr();
        $whereStr      = $this->createWhereConditionStr();
        $groupByColStr = $this->createGroupByColStr();
        $orderByStr    = $this->createOrderByStr();
        $preStr        = "SELECT $selectColStr FROM $tablesStr $lJoinStr $rJoinStr $whereStr $groupByColStr $orderByStr";
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