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

class Delete extends Operate
{
    protected $table;

    /**
     * DBOperateInterface constructor.
     *
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    private $limitStart, $limitEnd;
    private              $orderByInfo     = [];
    private              $whereConditions = [];

    public function where(Condition ...$conditions)
    {
        $this->whereConditions = array_merge($this->whereConditions, $conditions);
        return $this;
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

    /**
     * @return string
     */
    public function prepareStr()
    {
        $tablesStr  = $this->table;
        $whereStr   = $this->createWhereConditionStr();
        $orderByStr = $this->createOrderByStr();
        $preStr     = "DELETE  FROM $tablesStr $whereStr $orderByStr";
        if (is_int($this->limitStart) && is_int($this->limitEnd)) {
            $preStr = "$preStr limit $this->limitStart,$this->limitEnd";
        }
        return preg_replace('/\s+/', ' ', $preStr);
    }

    /**
     * @return string
     */
    private function createWhereConditionStr()
    {
        if (!empty($this->whereConditions)) {
            return 'WHERE ' . self::createConditionArrStr($this->whereConditions);
        }
        return '';
    }

    /**
     * @param array $conditionArr
     *
     * @return string
     */
    private static function createConditionArrStr(array $conditionArr)
    {
        if (empty($conditionArr)) {
            return '1';
        }
        $conditionGroup = [];
        foreach ($conditionArr as $condition) {
            if ($condition instanceof Condition) {
                $conditionGroup[$condition->getGroupName()][] = (string)$condition;
            }
        }
        foreach ($conditionGroup as $key => $item) {
            $conditionGroup[$key] = '(' . implode(' AND ', $item) . ')';
        }
        return implode(' OR ', $conditionGroup);
    }

    public function createOrderByStr()
    {
        if (empty($this->orderByInfo)) {
            return '';
        } else {
            return 'ORDER BY ' . implode(',', $this->orderByInfo);
        }
    }

    /**
     * @return array
     */
    public function prepareValues()
    {
        $whereConditionValues = $this->createWhereJoinConditionValueArr();
        return $whereConditionValues;
    }

    /**
     * @return array
     */
    private function createWhereJoinConditionValueArr()
    {
        return self::createConditionValueArr($this->whereConditions);
    }

    /**
     * @param array ...$conditionArr
     *
     * @return array
     */
    private static function createConditionValueArr(...$conditionArr)
    {
        $values       = [];
        $conditionArr = ArrayHelper::flatten($conditionArr);
        foreach ($conditionArr as $condition) {
            if ($condition instanceof Condition) {
                if (($v = $condition->getValue()) !== false) {
                    $values[] = $v;
                }
            }
        }
        return ArrayHelper::flatten($values);
    }
}