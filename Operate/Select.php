<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:29
 */

namespace DBOperate\Operate;

use DBOperate\ArrayHelper;
use DBOperate\Collection;
use DBOperate\Column;
use DBOperate\Condition;
use DBOperate\Operate;

class Select extends Operate implements Collection
{
    private $fetchColumns = [];
    private $limitStart, $limitEnd;

    private $groupByColumns  = [];
    private $orderByInfo     = [];
    private $whereConditions = [];
    private $innerJoinInfo   = [];
    private $lJoinInfo       = [];
    private $rJoinInfo       = [];
    private $forUpdate       = false;
    private $matchInfo       = null;
    /**
     * @var Collection
     */
    protected $collection;
    private   $aliasIndex = 0;

    private $selectOperates = [];

    /**
     * Select constructor.
     *
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        static $aliasCount = 0;
        $aliasCount++;
        $this->aliasIndex = $aliasCount;
        $this->collection = $collection;
    }

    public function fetchCols(Column ...$cols)
    {
        $this->fetchColumns = array_merge($this->fetchColumns, $cols);
        return $this;
    }

    public function clearFetchCols()
    {
        $this->fetchColumns = [];
        return $this;
    }

    public function forUpdate(bool $forUpdate)
    {
        $this->forUpdate = $forUpdate;
    }

    public function where(Condition ...$conditions)
    {
        $this->whereConditions = array_merge($this->whereConditions, $conditions);
        return $this;
    }

    public function matchAgainst(bool $boolMode, string $searchText, Column ...$cols)
    {
        $matchMode       = $boolMode ? 'IN BOOLEAN MODE' : 'IN NATURAL LANGUAGE MODE';
        $index           = implode(',', $cols);
        $this->matchInfo = ['matchMode' => $matchMode, 'searchText' => $searchText, 'index' => $index];
    }

    public function join(Collection $collection, Condition ...$conditions)
    {
        $this->innerJoinInfo[] = [$collection, $conditions];
        return $this;
    }

    public function lJoin(Collection $collection, Condition ...$conditions)
    {
        $this->lJoinInfo[] = [$collection, $conditions];
        return $this;
    }

    public function rJoin(Collection $collection, Condition ...$conditions)
    {
        $this->rJoinInfo[] = [$collection, $conditions];
        return $this;
    }

    public function groupBy(Column ...$cols)
    {
        $this->groupByColumns = array_merge($this->groupByColumns, $cols);
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
        $collectionStr = $this->getCollectionStr($this->collection);
        $selectColStr  = $this->createSelectColStr();
        $joinStr       = $this->innerJoinStr();
        $lJoinStr      = $this->lJoinStr();
        $rJoinStr      = $this->rJoinStr();
        $whereStr      = $this->whereConditionStr();
        $groupByColStr = $this->groupByColStr();
        $orderByStr    = $this->orderByStr();
        $preStr        = <<<TAG
SELECT $selectColStr FROM $collectionStr $joinStr $lJoinStr $rJoinStr $whereStr $groupByColStr $orderByStr
TAG;
        if (is_int($this->limitStart) && is_int($this->limitEnd)) {
            $preStr = "$preStr limit $this->limitStart,$this->limitEnd";
        }
        if ($this->forUpdate) {
            $preStr = "$preStr FOR UPDATE";
        }
        if (!empty($this->selectOperates)) {
            foreach ($this->selectOperates as $unionSelectOperate) {
                $operate = $unionSelectOperate['type'];
                /** @var Select $operatedSelect */
                $operatedSelect = $unionSelectOperate['select'];
                $preStr         = "$preStr $operate {$operatedSelect->prepareStr()}";
            }
        }
        return preg_replace('/\s+/', ' ', $preStr);
    }

    private function getCollectionStr(Collection $collection): string
    {
        if ($collection instanceof Select) {
            return "({$collection->prepareStr()}) `{$collection->getReferenceName()}`";
        } else {
            return (string)$collection;
        }
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

    public function union(Select $select)
    {
        $this->selectOperates[] = ['type' => 'UNION', 'select' => $select];
    }

    public function except(Select $select)
    {
        $this->selectOperates[] = ['type' => 'EXCEPT', 'select' => $select];
    }

    public function intersect(Select $select)
    {
        $this->selectOperates[] = ['type' => 'INTERSECT', 'select' => $select];
    }

    public function unionAll(Select $select)
    {
        $this->selectOperates[] = ['type' => 'UNION ALL', 'select' => $select];
    }

    /**
     * @return string
     */
    private function innerJoinStr()
    {
        return self::createJoinStr($this->innerJoinInfo, '');
    }

    /**
     * @param $joinInfo
     * @param $joinDirection
     *
     * @return string
     */
    private static function createJoinStr($joinInfo, $joinDirection)
    {
        $joinStrArr = [];
        foreach ($joinInfo as $joinItem) {
            $conditionArr = $joinItem[1];
            $conditionStr = self::createConditionArrStr($conditionArr);
            $collection   = $joinItem[0];
            if ($collection instanceof Select) {
                $collectionStr = $collection->getCollectionStr($collection);
            } else {
                $collectionStr = (string)$collection;
            }
            $joinStrArr[] = "$joinDirection JOIN $collectionStr ON $conditionStr";
        }
        return implode(' ', $joinStrArr);
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

    /**
     * @return string
     */
    private function lJoinStr()
    {
        return self::createJoinStr($this->lJoinInfo, 'LEFT');
    }

    /**
     * @return string
     */
    private function rJoinStr()
    {
        return self::createJoinStr($this->rJoinInfo, 'RIGHT');
    }

    /**
     * @return string
     */
    private function whereConditionStr()
    {
        if (!empty($this->whereConditions)) {
            $conditionStr = self::createConditionArrStr($this->whereConditions);
        }
        if (!empty($this->matchInfo)) {
            ['matchMode' => $matchMode, 'index' => $index] = $this->matchInfo;
            $matchStr = "MATCH($index) AGAINST (? $matchMode)";
        }
        if (isset($conditionStr)) {
            $whereStr = 'WHERE ' . $conditionStr;
        }
        if (isset($matchStr)) {
            $whereStr = isset($whereStr) ? "$whereStr AND $matchStr" : "WHERE $matchStr";
        }
        return $whereStr ?? '';
    }

    public function groupByColStr()
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

    public function orderByStr()
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
        if ($this->collection instanceof Select) {
            $collectionValues = $this->collection->prepareValues();
        } else {
            $collectionValues = [];
        }
        $innerCollectionValues = $this->innerJoinCollectionValueArr();
        $innerConditionValues  = $this->innerJoinConditionValueArr();
        $lCollectionValues     = $this->lJoinCollectionValueArr();
        $lConditionValues      = $this->lJoinConditionValueArr();
        $rCollectionValues     = $this->rJoinCollectionValueArr();
        $rConditionValues      = $this->rJoinConditionValueArr();
        $whereConditionValues  = $this->whereConditionValueArr();
        $unionSelectValues     = $this->unionSelectValueArr();
        return array_merge($collectionValues, $innerCollectionValues, $innerConditionValues, $lCollectionValues,
            $lConditionValues, $rCollectionValues, $rConditionValues,
            $whereConditionValues, $unionSelectValues);
    }

    /**
     * @return array
     */
    private function innerJoinConditionValueArr()
    {
        $conditionArr = array_column($this->innerJoinInfo, 1);
        return self::createConditionValueArr($conditionArr);
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

    /**
     * @return array
     */
    private function lJoinConditionValueArr()
    {
        $conditionArr = array_column($this->lJoinInfo, 1);
        return self::createConditionValueArr($conditionArr);
    }

    /**
     * @return array
     */
    private function rJoinConditionValueArr()
    {
        $conditionArr = array_column($this->rJoinInfo, 1);
        return self::createConditionValueArr($conditionArr);
    }

    /**
     * @return array
     */
    private function whereConditionValueArr()
    {
        $whereConditionValueArr = self::createConditionValueArr($this->whereConditions);
        if (!empty($this->matchInfo)) {
            $whereConditionValueArr[] = $this->matchInfo['searchText'];
        }
        return $whereConditionValueArr;
    }

    public function getReferenceName(): string
    {
        return "s{$this->aliasIndex}";
    }

    private function innerJoinCollectionValueArr()
    {
        $valuesArr       = [];
        $joinCollections = array_column($this->innerJoinInfo, 0);
        foreach ($joinCollections as $collection) {
            if ($collection instanceof Select) {
                $valuesArr = array_merge($valuesArr, $collection->prepareValues());
            }
        }
        return $valuesArr;
    }

    private function lJoinCollectionValueArr()
    {
        $valuesArr       = [];
        $joinCollections = array_column($this->lJoinInfo, 0);
        foreach ($joinCollections as $collection) {
            if ($collection instanceof Select) {
                $valuesArr = array_merge($valuesArr, $collection->prepareValues());
            }
        }
        return $valuesArr;
    }

    private function rJoinCollectionValueArr()
    {
        $valuesArr       = [];
        $joinCollections = array_column($this->rJoinInfo, 0);
        foreach ($joinCollections as $collection) {
            if ($collection instanceof Select) {
                $valuesArr = array_merge($valuesArr, $collection->prepareValues());
            }
        }
        return $valuesArr;
    }

    private function unionSelectValueArr()
    {
        $valuesArr = [];
        if (!empty($this->selectOperates)) {
            foreach ($this->selectOperates as $unionSelectOperate) {
                /** @var Select $select */
                $select    = $unionSelectOperate['select'];
                $valuesArr = array_merge($valuesArr, $select->prepareValues());;
            }
        }
        return $valuesArr;
    }
}