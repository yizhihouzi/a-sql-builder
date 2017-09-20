<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:17
 */

namespace DBOperate;


abstract class Operate
{
    protected $table;

    protected $groupByColumns  = [];
    protected $whereConditions = [];
    protected $lJoinInfo       = [];
    protected $rJoinInfo       = [];

    /**
     * DBOperateInterface constructor.
     *
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function groupBy(Column ...$cols)
    {
        $this->groupByColumns = array_merge($this->groupByColumns, $cols);
        return $this;
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
        $conditionArr = self::flatten($conditionArr);
        foreach ($conditionArr as $condition) {
            if ($condition instanceof Condition) {
                if (($v = $condition->getValue()) !== false) {
                    $values[] = $v;
                }
            } else {
                throw new \Exception("$condition can not transform to Condition type");
            }
        }
        return self::flatten($values);
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

    protected function createLJoinStr()
    {
        return self::createJoinStr($this->lJoinInfo, 'LEFT');
    }

    protected function createLJoinConditionValueArr()
    {
        $conditionArr = self::pluck($this->lJoinInfo, 1);
        return self::createConditionValueArr($conditionArr);
    }

    protected function createRJoinConditionValueArr()
    {
        $conditionArr = self::pluck($this->rJoinInfo, 1);
        return self::createConditionValueArr($conditionArr);
    }

    protected function createRJoinStr()
    {
        return self::createJoinStr($this->rJoinInfo, 'RIGHT');
    }

    protected function createWhereConditionStr()
    {
        if (!empty($this->whereConditions)) {
            return 'WHERE ' . self::createConditionArrStr($this->whereConditions);
        }
        return '';
    }

    protected function createWhereJoinConditionValueArr()
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

    /**
     * Flattens a multidimensional array. If you pass shallow, the array will only be flattened a single level.
     *
     * __::flatten([1, 2, [3, [4]]], [flatten]);
     *      >> [1, 2, 3, 4]
     *
     * @param      $array
     * @param bool $shallow
     *
     * @return array
     *
     */
    protected static function flatten($array, $shallow = false)
    {
        $output = [];
        foreach ($array as $value) {
            if (is_array($value)) {
                if (!$shallow) {
                    $value = self::flatten($value, $shallow);
                }
                foreach ($value as $valItem) {
                    $output[] = $valItem;
                }
            } else {
                $output[] = $value;
            }
        }
        return $output;
    }

    /**
     * Returns an array of values belonging to a given property of each item in a collection.
     *
     * @param array  $collection array
     * @param string $property   property name
     *
     * @return array
     */
    private static function pluck(array $collection, $property)
    {
        return \array_map(function ($value) use ($property) {
            if (isset($value[$property])) {
                return $value[$property];
            }

            foreach (\explode('.', $property) as $segment) {
                if (\is_object($value)) {
                    if (isset($value->{$segment})) {
                        $value = $value->{$segment};
                    } else {
                        return null;
                    }
                } else {
                    if (isset($value[$segment])) {
                        $value = $value[$segment];
                    } else {
                        return null;
                    }
                }
            }

            return $value;
        }, (array)$collection);
    }

    function __toString()
    {
        return json_encode([$this->prepareStr(), $this->prepareValues()]);
    }

    public function toTestSql()
    {
        $preStr    = $this->prepareStr();
        $preValues = $this->prepareValues();
        $preStr    = str_replace('?', '\'%s\'', $preStr);
        return vsprintf($preStr, $preValues);
    }

    public abstract function prepareStr();

    public abstract function prepareValues();
}