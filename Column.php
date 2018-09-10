<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:19
 */

namespace DBOperate;

/**
 * Class Column
 *  应该允许存在不属于任何表的列
 * @package DBOperate
 */
class Column
{
    private $collection;
    private $col;
    private $alias;

    /**
     * Column constructor.
     *
     * @param string     $col
     * @param Collection $collection
     * @param string     $alias
     */
    public function __construct(string $col, Collection $collection = null, string $alias = null)
    {
        $this->collection = $collection;
        $this->col        = $col;
        $this->alias      = $alias;
    }

    public function toSelectColStr()
    {
        $col = "`$this->col`";
        if (($this->collection) instanceof Collection) {
            $collectionName = $this->collection->getReferenceName();
            $col            = "`$collectionName`.$col";
        }
        if (is_string($this->alias)) {
            $col = "$col `$this->alias`";
        }
        return $col;
    }

    public function createCondition($value, $relation = '=', $groupName = 'e')
    {
        return new Condition($this, $value, $relation, $groupName);
    }

    public function alias(string $aliasName)
    {
        $this->alias = $aliasName;
        return $this;
    }

    public function colName()
    {
        return $this->col;
    }

    public function aliasName()
    {
        return $this->alias;
    }

    public function __toString()
    {
        return $this->toSelectColStr();
    }
}