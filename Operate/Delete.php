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

class Delete extends Operate
{
    private $limitStart, $limitEnd;
    private              $orderByInfo     = [];
    private              $whereConditions = [];

    public function where(Condition ...$conditions)
    {
        $this->whereConditions = array_merge($this->whereConditions, $conditions);
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
        $tablesStr  = $this->table;
        $whereStr   = $this->createWhereConditionStr();
        $orderByStr = $this->createOrderByStr();
        $preStr     = "DELETE  FROM $tablesStr $whereStr $orderByStr";
        if (is_int($this->limitStart) && is_int($this->limitEnd)) {
            $preStr = "$preStr limit $this->limitStart,$this->limitEnd";
        }
        return $preStr;
    }

    public function prepareValues()
    {
        $whereConditionValues = $this->createWhereJoinConditionValueArr();
        return $whereConditionValues;
    }
}